<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartAbstractPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercyPayWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $type = (string) $request->input('type');
        $data = (array) $request->input('data', []);

        match ($type) {
            'payment.succeeded' => $this->markPayment($data, SmartAbstractPayment::STATUS_COMPLETED),
            'payment.failed' => $this->markPayment($data, SmartAbstractPayment::STATUS_FAILED),
            default => Log::info('Mercy Pay webhook ignored.', ['type' => $type]),
        };

        // Toujours repondre 200 une fois la signature validee, meme pour un
        // evenement non gere, sinon Mercy Pay reessaiera indefiniment.
        return response()->json(['success' => true]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('mercypay.webhook_secret');

        if ($secret === '') {
            Log::warning('MERCYPAY_WEBHOOK_SECRET is missing: rejecting webhook.');

            return false;
        }

        $received = (string) $request->header('X-MercyPay-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return $received !== '' && hash_equals($expected, $received);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function markPayment(array $data, string $status): void
    {
        $checkoutSessionReference = $data['checkout_session'] ?? null;

        if (! is_string($checkoutSessionReference) || $checkoutSessionReference === '') {
            Log::info('Mercy Pay webhook without checkout_session: ignored (direct charge, not smart abstract).');

            return;
        }

        $payment = SmartAbstractPayment::query()
            ->where('checkout_reference', $checkoutSessionReference)
            ->where('status', SmartAbstractPayment::STATUS_PENDING)
            ->first();

        if (! $payment instanceof SmartAbstractPayment) {
            // Deja traite (webhook rejoue) ou reference inconnue : on ignore
            // silencieusement pour rester idempotent.
            return;
        }

        $payment->update(['status' => $status]);
    }
}
