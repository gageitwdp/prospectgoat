<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadIntakeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

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
            ->subject('New lead inquiry received')
            ->markdown('emails.notifications.new-lead-intake', [
                'notifiable' => $notifiable,
                'lead' => $this->lead,
                'leadType' => str_replace('_', ' ', $this->lead->lead_type),
                'source' => str_replace('_', ' ', $this->lead->source),
                'leadUrl' => route('manager.leads.show', $this->lead),
            ]);
    }
}
