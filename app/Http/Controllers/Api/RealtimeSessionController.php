<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversationSession;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RealtimeSessionController extends Controller
{
    // AI sessions are not product-limited anymore. This constant only keeps a
    // technical upper bound in the database/OpenAI session.
    private const UNLIMITED_SESSION_SECONDS = 14400; // 4h

    public function start(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->expireElapsedSessionsFor($user);

        $quota = $this->formatQuotaFor($user);

        $validated = $request->validate([
            'topic_name' => ['nullable', 'string', 'max:120'],
            'topic_id' => ['nullable'],
            'bonus_minutes' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $now = now();
        $session = AiConversationSession::query()->create([
            'user_id' => $user->id,
            'session_limit_seconds' => self::UNLIMITED_SESSION_SECONDS,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => $now,
            'expires_at' => $now->copy()->addSeconds(self::UNLIMITED_SESSION_SECONDS),
        ]);

        $realtime = $this->createOpenAiClientSecret(
            user: $user,
            topicName: $validated['topic_name'] ?? 'Conversation libre',
            sessionLimitSeconds: $session->session_limit_seconds,
        );

        return response()->json([
            'success' => true,
            'message' => 'Realtime session created.',
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'topic_name' => $validated['topic_name'] ?? 'Conversation libre',
                    'target_language' => 'en',
                    'level' => 1,
                    'status' => $session->status,
                    'session_limit_seconds' => $session->session_limit_seconds,
                    'remaining_seconds' => max(0, (int) now()->diffInSeconds($session->expires_at, false)),
                ],
                'quota' => $quota,
                'realtime' => $realtime,
            ],
        ], 201);
    }

    public function end(Request $request, AiConversationSession $session): JsonResponse
    {
        $user = $this->userFrom($request);
        abort_unless($session->user_id === $user->id, 403);

        if ($session->status === AiConversationSession::STATUS_ACTIVE) {
            $session->update([
                'status' => $session->expires_at->isPast()
                    ? AiConversationSession::STATUS_EXPIRED
                    : AiConversationSession::STATUS_COMPLETED,
                'ended_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Realtime session ended.',
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'status' => $session->fresh()?->status ?? $session->status,
                ],
            ],
        ]);
    }

    private function createOpenAiClientSecret(User $user, string $topicName, int $sessionLimitSeconds): array
    {
        $apiKey = config('ai.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw ValidationException::withMessages([
                'openai' => ['OPENAI_API_KEY est manquant dans le fichier .env.'],
            ]);
        }

        $model = (string) config('ai.openai.realtime_model');
        $voice = (string) config('ai.openai.realtime_voice');
        $baseUrl = rtrim((string) config('ai.openai.realtime_url'), '/');
        $timeout = (int) config('ai.openai.timeout');
        $instructions = $this->instructionsFor($user, $topicName);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($apiKey)
                ->post("{$baseUrl}/realtime/client_secrets", [
                    'session' => [
                        'type' => 'realtime',
                        'model' => $model,
                        'instructions' => $instructions,
                        'output_modalities' => ['audio'],
                        'max_output_tokens' => config('ai.openai.realtime_max_output_tokens'),
                        'include' => ['item.input_audio_transcription.logprobs'],
                        'audio' => [
                            'input' => [
                                'format' => ['type' => 'audio/pcm', 'rate' => 24000],
                                'transcription' => [
                                    'model' => 'gpt-4o-mini-transcribe',
                                    'language' => 'en',
                                ],
                                'turn_detection' => [
                                    'type' => 'semantic_vad',
                                    'eagerness' => 'high',
                                    'create_response' => false,
                                    'interrupt_response' => false,
                                ],
                            ],
                            'output' => [
                                'format' => ['type' => 'audio/pcm', 'rate' => 24000],
                                'voice' => $voice,
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenAI Realtime is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'openai' => ['OpenAI Realtime error: '.$response->body()],
            ]);
        }

        $clientSecret = $response->json('client_secret.value')
            ?? $response->json('value');

        if (! is_string($clientSecret) || trim($clientSecret) === '') {
            throw new RuntimeException('OpenAI Realtime client secret response is invalid.');
        }

        return [
            'client_secret' => $clientSecret,
            'model' => $model,
            'voice' => $voice,
            'url' => "wss://api.openai.com/v1/realtime?model={$model}",
            'instructions' => $instructions,
            'target_language' => 'en',
            'native_language' => 'fr',
            'input_sample_rate' => 24000,
            'output_sample_rate' => 24000,
            'session_minutes_limit' => (int) ceil($sessionLimitSeconds / 60),
            'base_session_minutes' => (int) ceil($sessionLimitSeconds / 60),
            'bonus_minutes_reserved' => 0,
            'limits' => [],
            'usage' => [],
        ];
    }

    private function instructionsFor(User $user, string $topicName): string
    {
        return <<<PROMPT
You are LexiCoach, a voice English coach for a dyslexic learner.
Default language: English.
Topic: {$topicName}

Rules:
- Start the conversation naturally in English.
- Keep speaking in English by default.
- Use short, clear sentences.
- Use one idea at a time.
- Ask only one question at a time.
- Correct gently, with one main correction maximum.
- Encourage the learner without infantilizing them.
- Help the learner speak with confidence.
- If the learner speaks French, answer briefly in English and guide them back to a simple English sentence.
PROMPT;
    }

    private function userFrom(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function expireElapsedSessionsFor(User $user): void
    {
        AiConversationSession::query()
            ->whereBelongsTo($user)
            ->where('status', AiConversationSession::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => AiConversationSession::STATUS_EXPIRED,
                'ended_at' => now(),
            ]);
    }

    private function formatQuotaFor(User $user): array
    {
        $usedToday = AiConversationSession::query()
            ->whereBelongsTo($user)
            ->whereDate('started_at', now()->toDateString())
            ->count();

        return [
            'daily_session_limit' => $user->ai_conversation_daily_session_limit,
            'daily_sessions_used' => $usedToday + 1,
            'daily_sessions_remaining' => max(0, $user->ai_conversation_daily_session_limit - ($usedToday + 1)),
        ];
    }
}
