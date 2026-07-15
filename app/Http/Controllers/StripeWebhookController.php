<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookPayloadJob;
use App\Services\Billing\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookService $webhooks) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $this->webhooks->assertValidSignature($payload, $signature);

            ProcessStripeWebhookPayloadJob::dispatch($payload, $signature);

            return response()->json(['received' => true]);
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            return response()->json(['message' => 'Invalid Stripe webhook signature.'], 400);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (\Throwable $exception) {
            Log::error('Stripe webhook processing failed.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }
    }
}