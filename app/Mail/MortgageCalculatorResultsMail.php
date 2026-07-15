<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MortgageCalculatorResultsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $inputs
     * @param array<string, float> $results
     */
    public function __construct(
        public string $fullName,
        public array $inputs,
        public array $results,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Mortgage Calculator Results',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mortgage-calculator-results',
            with: [
                'fullName' => $this->fullName,
                'inputs' => $this->inputs,
                'results' => $this->results,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
