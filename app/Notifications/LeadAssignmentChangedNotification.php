<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadAssignmentChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public string $previousAssignee,
        public ?string $assignedBy,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $email = method_exists($notifiable, 'routeNotificationForMail')
            ? $notifiable->routeNotificationForMail($this)
            : ($notifiable->email ?? null);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? ['mail'] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A lead has been assigned to you')
            ->markdown('emails.notifications.lead-assignment-changed', [
                'notifiable' => $notifiable,
                'lead' => $this->lead,
                'previousAssignee' => $this->previousAssignee,
                'assignedBy' => $this->assignedBy,
                'leadUrl' => route('manager.leads.show', $this->lead),
            ]);
    }
}
