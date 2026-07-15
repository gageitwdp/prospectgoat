<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventSignupThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public bool $isWorkingWithAgent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for attending our open house',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-signup-thank-you',
            with: [
                'firstName' => $this->firstName,
                'isWorkingWithAgent' => $this->isWorkingWithAgent,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}