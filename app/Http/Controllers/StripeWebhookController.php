<?php

namespace App\Http\Controllers;

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
        try {
            $this->webhooks->handleSignedPayload(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
            );

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