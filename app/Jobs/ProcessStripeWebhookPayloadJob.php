<?php

namespace App\Jobs;

use App\Services\Billing\StripeWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessStripeWebhookPayloadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $payload,
        private readonly string $signature,
    ) {
        $this->onQueue('billing');
    }

    public function handle(StripeWebhookService $webhooks): void
    {
        $webhooks->handleSignedPayload($this->payload, $this->signature);
    }
}
