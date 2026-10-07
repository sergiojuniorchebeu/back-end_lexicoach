<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversationMessage;
use App\Models\AiConversationSession;
use App\Models\User;
use App\Services\Ai\AiFeedbackService;
use App\Services\Ai\GeminiLiveTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiConversationSessionController extends Controller
{
    // AI sessions are not product-limited anymore. This constant only keeps a
    // technical upper bound in the database/OpenAI session.
    private const UNLIMITED_SESSION_SECONDS = 14400; // 4h

    public function __construct(
        private readonly AiFeedbackService $aiFeedbackService,
        private readonly GeminiLiveTokenService $geminiLiveTokenService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->expireElapsedSessionsFor($user);

        $activeSession = $this->activeSessionFor($user);

        if ($activeSession instanceof AiConversationSession) {
            return response()->json([
                'success' => true,
                'message' => 'Active AI conversation session retrieved.',
                'data' => [
                    'session' => $this->formatSession($activeSession),
                    'quota' => $this->formatQuotaFor($user),
                    'realtime' => $this->realtimeFor($activeSession),
                ],
            ]);
        }

        $now = now();
        $session = AiConversationSession::query()->create([
            'user_id' => $user->id,
            'session_limit_seconds' => self::UNLIMITED_SESSION_SECONDS,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => $now,
            'expires_at' => $now->copy()->addSeconds(self::UNLIMITED_SESSION_SECONDS),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI conversation session created.',
            'data' => [
                'session' => $this->formatSession($session),
                'quota' => $this->formatQuotaFor($user),
                'realtime' => $this->realtimeFor($session),
            ],
        ], 201);
    }

    public function end(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);

        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        if ($aiConversationSession->status === AiConversationSession::STATUS_ACTIVE) {
            $aiConversationSession->update([
                'status' => $aiConversationSession->expires_at->isPast()
                    ? AiConversationSession::STATUS_EXPIRED
                    : AiConversationSession::STATUS_COMPLETED,
                'ended_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'AI conversation session ended.',
            'data' => [
                'session' => $this->formatSession($aiConversationSession->fresh() ?? $aiConversationSession),
                'quota' => $this->formatQuotaFor($user),
            ],
        ]);
    }

    public function realtimeAuthorize(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->expireElapsedSessionsFor($user);

        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        if ($aiConversationSession->status !== AiConversationSession::STATUS_ACTIVE
            || $aiConversationSession->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'session' => ['This AI conversation session is no longer active.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Realtime session authorized.',
            'data' => [
                'session' => $this->formatSession($aiConversationSession),
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->name,
                    'role' => $user->role,
                ],
                'realtime' => [
                    'model' => config('ai.gemini.live_model'),
                    'response_modality' => config('ai.gemini.live_response_modality'),
                    'connection_mode' => 'direct_ephemeral_token',
                ],
            ],
        ]);
    }

    public function expire(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);

        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        if ($aiConversationSession->status === AiConversationSession::STATUS_ACTIVE) {
            $aiConversationSession->update([
                'status' => AiConversationSession::STATUS_EXPIRED,
                'ended_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'AI conversation session expired.',
            'data' => [
                'session' => $this->formatSession($aiConversationSession->fresh() ?? $aiConversationSession),
                'quota' => $this->formatQuotaFor($user),
            ],
        ]);
    }

    public function messages(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        $messages = $aiConversationSession->messages()
            ->get()
            ->map(fn (AiConversationMessage $message): array => $this->formatMessage($message));

        return response()->json([
            'success' => true,
            'message' => 'AI conversation messages retrieved.',
            'data' => [
                'messages' => $messages,
            ],
        ]);
    }

    public function storeMessage(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in([
                AiConversationMessage::ROLE_LEARNER,
                AiConversationMessage::ROLE_ASSISTANT,
            ])],
            'content' => ['required', 'string', 'max:4000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $message = $aiConversationSession->messages()->create([
            'role' => $validated['role'],
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI conversation message saved.',
            'data' => [
                'message' => $this->formatMessage($message),
            ],
        ], 201);
    }

    public function turn(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        if ($aiConversationSession->status !== AiConversationSession::STATUS_ACTIVE
            || $aiConversationSession->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'session' => ['This AI conversation session is no longer active.'],
            ]);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1000'],
        ]);
        $turnStartedAt = microtime(true);
        $learnerTranscript = trim($validated['message']);

        $this->logConversationDebug('AI turn received', [
            'session_id' => $aiConversationSession->id,
            'user_id' => $user->id,
            'message_length' => mb_strlen($learnerTranscript),
            'learner_transcript' => $learnerTranscript,
            'remaining_seconds' => max(0, (int) now()->diffInSeconds($aiConversationSession->expires_at, false)),
        ]);

        $learnerMessage = $aiConversationSession->messages()->create([
            'role' => AiConversationMessage::ROLE_LEARNER,
            'content' => $learnerTranscript,
            'metadata' => [
                'source' => 'mobile_voice_turn',
            ],
        ]);

        $context = $aiConversationSession->messages()
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiConversationMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();

        $this->logConversationDebug('AI turn context prepared', [
            'session_id' => $aiConversationSession->id,
            'user_id' => $user->id,
            'context_count' => count($context),
            'context' => $context,
        ]);

        $reply = $this->aiFeedbackService->replyToConversation($context);

        $this->logConversationDebug('AI turn reply generated', [
            'session_id' => $aiConversationSession->id,
            'user_id' => $user->id,
            'intent' => $reply['intent'] ?? null,
            'task' => $reply['task'] ?? null,
            'fallback_used' => $reply['fallback_used'] ?? null,
            'fallback_reason' => $reply['fallback_reason'] ?? null,
            'reply' => $reply['reply'] ?? null,
        ]);

        $assistantMessage = $aiConversationSession->messages()->create([
            'role' => AiConversationMessage::ROLE_ASSISTANT,
            'content' => $reply['reply'],
            'metadata' => [
                'source' => 'laravel_ai_agent',
                'gentle_correction' => $reply['gentle_correction'],
                'encouragement' => $reply['encouragement'],
                'next_question' => $reply['next_question'],
            ],
        ]);

        $this->logConversationDebug('AI turn saved', [
            'session_id' => $aiConversationSession->id,
            'user_id' => $user->id,
            'learner_message_id' => $learnerMessage->id,
            'assistant_message_id' => $assistantMessage->id,
            'duration_ms' => (int) round((microtime(true) - $turnStartedAt) * 1000),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI conversation turn processed.',
            'data' => [
                'learner_message' => $this->formatMessage($learnerMessage),
                'assistant_message' => $this->formatMessage($assistantMessage),
                'coach' => $reply,
            ],
        ], 201);
    }

    public function assess(Request $request, AiConversationSession $aiConversationSession): JsonResponse
    {
        $user = $this->userFrom($request);
        $this->abortUnlessOwnsSession($user, $aiConversationSession);

        $messages = $aiConversationSession->messages()->get();

        if ($messages->where('role', AiConversationMessage::ROLE_LEARNER)->isEmpty()) {
            throw ValidationException::withMessages([
                'messages' => ['The session does not contain any learner message to assess yet.'],
            ]);
        }

        $result = $this->aiFeedbackService->evaluateConversation(
            $messages
                ->map(fn (AiConversationMessage $message): array => [
                    'role' => $message->role,
                    'content' => $message->content,
                ])
                ->all(),
        );

        $aiConversationSession->update([
            'assessment_score' => $result['score'],
            'assessment_status' => $result['status'],
            'assessment_feedback' => $result,
            'assessed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI conversation progress evaluated.',
            'data' => [
                'session' => $this->formatSession($aiConversationSession->fresh() ?? $aiConversationSession),
                'assessment' => $result,
            ],
        ]);
    }

    private function userFrom(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function abortUnlessOwnsSession(User $user, AiConversationSession $session): void
    {
        abort_unless($session->user_id === $user->id, 403);
    }

    private function activeSessionFor(User $user): ?AiConversationSession
    {
        return AiConversationSession::query()
            ->whereBelongsTo($user)
            ->where('status', AiConversationSession::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->latest('started_at')
            ->first();
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

    /**
     * @return array<string, mixed>
     */
    private function formatQuotaFor(User $user): array
    {
        $usedToday = AiConversationSession::query()
            ->whereBelongsTo($user)
            ->whereDate('started_at', now()->toDateString())
            ->count();

        return [
            'daily_session_limit' => $user->ai_conversation_daily_session_limit,
            'daily_sessions_used' => $usedToday,
            'daily_sessions_remaining' => max(0, $user->ai_conversation_daily_session_limit - $usedToday),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSession(AiConversationSession $session): array
    {
        return [
            'id' => $session->id,
            'status' => $session->status,
            'session_limit_seconds' => $session->session_limit_seconds,
            'started_at' => $session->started_at,
            'expires_at' => $session->expires_at,
            'ended_at' => $session->ended_at,
            'remaining_seconds' => $session->status === AiConversationSession::STATUS_ACTIVE
                ? max(0, (int) now()->diffInSeconds($session->expires_at, false))
                : 0,
            'assessment_score' => $session->assessment_score,
            'assessment_status' => $session->assessment_status,
            'assessment_feedback' => $session->assessment_feedback,
            'assessed_at' => $session->assessed_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(AiConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'metadata' => $message->metadata,
            'created_at' => $message->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function realtimeFor(AiConversationSession $session): array
    {
        $direct = $this->geminiLiveTokenService->createForSession($session);

        return [
            'connection_mode' => 'direct_ephemeral_token',
            'direct' => $direct,
            'fallback_proxy' => [
                'websocket_url' => config('ai.gemini.live_proxy_url'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logConversationDebug(string $message, array $context): void
    {
        if (! config('ai.conversation_debug_logs', true)) {
            return;
        }

        Log::info($message, $context);
    }
}
