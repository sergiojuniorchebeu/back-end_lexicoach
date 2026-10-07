<?php

namespace App\Services\Ai;

use App\Models\AiConversationSession;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLiveTokenService
{
    public function __construct(
        private readonly GeminiKeyPool $keyPool = new GeminiKeyPool,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function createForSession(AiConversationSession $session): ?array
    {
        if (! $this->keyPool->hasAnyKey()) {
            return null;
        }

        $baseUrl = rtrim((string) config('ai.gemini.base_url'), '/');
        $model = (string) config('ai.gemini.live_model');
        $responseModality = (string) config('ai.gemini.live_response_modality');
        $timeout = (int) config('ai.gemini.timeout');
        $expireAt = $this->tokenExpireAt($session);

        $apiKey = $this->keyPool->currentKey();
        $attemptsLeft = 2; // la cle courante, puis une seule bascule de secours

        while (true) {
            if ($apiKey === null) {
                throw new RuntimeException('Toutes les cles Gemini sont epuisees.');
            }

            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post("{$baseUrl}/auth_tokens", [
                        'uses' => 1,
                        'expireTime' => $expireAt->toISOString(),
                        'newSessionExpireTime' => now()->addMinute()->toISOString(),
                    ]);
            } catch (ConnectionException $exception) {
                throw new RuntimeException('Gemini ephemeral token service is unreachable: '.$exception->getMessage(), previous: $exception);
            }

            $attemptsLeft--;
            $isQuotaExhausted = $response->status() === 429
                || ($response->status() === 403 && str_contains($response->body(), 'RESOURCE_EXHAUSTED'));

            if ($isQuotaExhausted && $attemptsLeft > 0) {
                $this->keyPool->markExhausted($apiKey);
                $apiKey = $this->keyPool->nextKeyAfter($apiKey);

                continue;
            }

            break;
        }

        if ($response->failed()) {
            throw new RuntimeException('Gemini ephemeral token request failed: '.$response->body());
        }

        $token = $response->json('name') ?? $response->json('authToken.name');

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Gemini ephemeral token response is invalid.');
        }

        return [
            'provider' => 'gemini',
            'auth_type' => 'ephemeral_token',
            'token' => $token,
            'model' => $model,
            'response_modality' => $responseModality,
            'websocket_url' => 'wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContentConstrained',
            'expires_at' => $expireAt,
            'system_instruction' => $this->systemInstruction(),
        ];
    }

    private function tokenExpireAt(AiConversationSession $session): CarbonInterface
    {
        $sessionExpiry = $session->expires_at instanceof CarbonInterface
            ? $session->expires_at
            : now()->addSeconds($session->session_limit_seconds);

        return $sessionExpiry->isFuture()
            ? $sessionExpiry
            : now()->addMinute();
    }

    private function systemInstruction(): string
    {
        return 'You are LexiCoach, a patient language learning coach for a dyslexic learner. Use short clear sentences. Ask one question at a time. Correct gently. Encourage the learner without infantilizing them. Focus on communication, confidence, simple vocabulary, and one practical improvement at a time.';
    }
}
