<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventSignupRequest;
use App\Mail\EventSignupThankYouMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\NewLeadIntakeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class EventController extends Controller
{
    public function index(): View
    {
        $accountId = $this->resolvePublicAccountId();

        $events = Event::query()
            ->where('account_id', $accountId)
            ->where('status', 'published')
            ->where('event_time', '>=', now())
            ->orderBy('event_time')
            ->get();

        return view('events.index', compact('events'));
    }

    public function signup(string $slug): View
    {
        $accountId = $this->resolvePublicAccountId();

        $event = Event::query()
            ->where('account_id', $accountId)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('events.signup', compact('event'));
    }

    public function storeSignup(StoreEventSignupRequest $request, string $slug): RedirectResponse
    {
        $accountId = $this->resolvePublicAccountId();

        $event = Event::query()
            ->where('account_id', $accountId)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $data = $request->validated();

        $lead = DB::transaction(function () use ($event, $data, $accountId): Lead {
            $lead = Lead::create([
            'account_id' => $accountId,
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $event->location,
                'lead_type' => 'generic_inquiry',
                'source' => 'landing_page',
                'status' => 'new',
                'assigned_to' => null,
                'working_with_agent' => (bool) $data['working_with_agent'],
            ]);

            EventRegistration::create([
                'account_id' => $accountId,
                'event_id' => $event->id,
                'lead_id' => $lead->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'working_with_agent' => (bool) $data['working_with_agent'],
                'agent_first_name' => $data['agent_first_name'] ?? null,
                'agent_last_name' => $data['agent_last_name'] ?? null,
            ]);

            $lead->activities()->create([
                'account_id' => $accountId,
                'type' => 'note',
                'description' => sprintf('Lead submitted from event signup: %s at %s on %s.', $event->name, $event->location, $event->event_time->format('M d, Y g:i A')),
            ]);

            $lead->activities()->create([
                'account_id' => $accountId,
                'type' => 'note',
                'description' => sprintf('Working with an agent: %s.', (bool) $data['working_with_agent'] ? 'Yes' : 'No'),
            ]);

            if ((bool) $data['working_with_agent']) {
                $lead->activities()->create([
                    'account_id' => $accountId,
                    'type' => 'note',
                    'description' => sprintf(
                        'Agent on signed agreement: %s %s.',
                        $data['agent_first_name'],
                        $data['agent_last_name'],
                    ),
                ]);
            }

            return $lead;
        });

        $this->notifyLeadRecipients($lead, $accountId);
        $this->sendAttendeeThankYouEmail($lead, $event, $data);

        return redirect()
            ->route('events.signup.show', $event->slug)
            ->with('status', 'Thanks for signing up. We have received your registration.');
    }

    private function notifyLeadRecipients(Lead $lead, int $accountId): void
    {
        if (Schema::hasColumn('users', 'notify_on_new_lead_intake')) {
            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereNotNull('email')
                ->get()
                ->filter(function (User $user): bool {
                    $role = strtolower(trim((string) $user->role));

                    if (in_array($role, ['owner', 'admin'], true)) {
                        return true;
                    }

                    if (in_array($role, ['manager', 'agent'], true)) {
                        return (bool) $user->notify_on_new_lead_intake;
                    }

                    return false;
                })
                ->values();
        } else {
            $recipients = User::query()
                ->where('account_id', $accountId)
                ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                ->whereNotNull('email')
                ->get();
        }

        foreach ($recipients as $recipient) {
            try {
                Notification::sendNow($recipient, new NewLeadIntakeNotification($lead));
            } catch (Throwable $exception) {
                Log::error('Event signup lead notification failed for recipient.', [
                    'lead_id' => $lead->id,
                    'recipient_id' => $recipient->id,
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function sendAttendeeThankYouEmail(Lead $lead, Event $event, array $data): void
    {
        if (! filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($lead->email)->send(new EventSignupThankYouMail(
                firstName: (string) ($data['first_name'] ?? ''),
                isWorkingWithAgent: (bool) ($data['working_with_agent'] ?? false),
            ));

            Log::info('Event signup attendee thank-you email sent.', [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
            ]);
        } catch (Throwable $exception) {
            Log::error('Event signup attendee thank-you email failed.', [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'lead_id' => $lead->id,
                'lead_email' => $lead->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
