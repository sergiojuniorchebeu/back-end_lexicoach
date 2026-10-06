<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MercyPayClient
{
    /**
     * Create a hosted checkout session.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCheckoutSession(array $payload, string $idempotencyKey): array
    {
        $apiKey = (string) config('mercypay.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('MERCYPAY_API_KEY is missing from .env.');
        }

        try {
            $response = Http::timeout((int) config('mercypay.timeout'))
                ->acceptJson()
                ->withToken($apiKey)
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post(rtrim((string) config('mercypay.base_url'), '/').'/api/v1/checkout-sessions', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Mercy Pay is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'mercypay' => ['Mercy Pay error: '.$response->body()],
            ]);
        }

        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['reference'], $data['checkout_url'])) {
            throw new RuntimeException('Mercy Pay checkout session response is invalid.');
        }

        return $data;
    }
}
