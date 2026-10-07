<?php

namespace Tests\Feature;

use App\Models\AiConversationSession;
use App\Models\AiConversationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiConversationSessionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.provider', 'fake');
        Config::set('ai.gemini.api_key', null);
    }

    public function test_learner_can_start_an_ai_conversation_session(): void
    {
        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson('/api/ai-conversations')
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.status', AiConversationSession::STATUS_ACTIVE)
            ->assertJsonPath('data.session.session_limit_seconds', 14400)
            ->assertJsonPath('data.quota.daily_session_limit', 3)
            ->assertJsonPath('data.quota.daily_sessions_used', 1)
            ->assertJsonPath('data.quota.daily_sessions_remaining', 2);

        $this->assertDatabaseHas('ai_conversation_sessions', [
            'user_id' => $learner->id,
            'session_limit_seconds' => 14400,
            'status' => AiConversationSession::STATUS_ACTIVE,
        ]);
    }

    public function test_start_can_return_gemini_ephemeral_token_when_key_is_configured(): void
    {
        Config::set('ai.gemini.api_key', 'test-gemini-key');
        Config::set('ai.gemini.live_model', 'gemini-3.8-live');
        Config::set('ai.gemini.live_response_modality', 'TEXT');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/auth_tokens' => Http::response([
                'name' => 'test-ephemeral-token',
            ]),
        ]);

        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson('/api/ai-conversations')
            ->assertCreated()
            ->assertJsonPath('data.realtime.connection_mode', 'direct_ephemeral_token')
            ->assertJsonPath('data.realtime.direct.token', 'test-ephemeral-token')
            ->assertJsonPath('data.realtime.direct.model', 'gemini-3.8-live')
            ->assertJsonPath('data.realtime.direct.response_modality', 'TEXT');

        Http::assertSent(fn ($request): bool => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/auth_tokens'
            && data_get($request->data(), 'uses') === 1
            && data_get($request->data(), 'liveConnectConstraints') === null);
    }

    public function test_start_returns_existing_active_session_without_consuming_another_quota(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->postJson('/api/ai-conversations')
            ->assertOk()
            ->assertJsonPath('data.session.id', $session->id)
            ->assertJsonPath('data.quota.daily_sessions_used', 1);

        $this->assertDatabaseCount('ai_conversation_sessions', 1);
    }

    public function test_learner_can_start_even_with_daily_limit_set_to_zero(): void
    {
        // Les limites de sessions IA ont ete volontairement supprimees :
        // la colonne existe encore (reglage admin legacy) mais n'est plus
        // appliquee nulle part.
        $learner = User::factory()->create([
            'ai_conversation_daily_session_limit' => 0,
        ]);

        Sanctum::actingAs($learner);

        $this->postJson('/api/ai-conversations')
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_learner_can_start_beyond_daily_session_limit(): void
    {
        $learner = User::factory()->create([
            'ai_conversation_daily_session_limit' => 1,
        ]);

        AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'expires_at' => now()->subHour()->addMinutes(3),
            'ended_at' => now()->subMinutes(57),
        ]);

        Sanctum::actingAs($learner);

        $this->postJson('/api/ai-conversations')
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_learner_can_end_their_active_session(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->patchJson("/api/ai-conversations/{$session->id}/end")
            ->assertOk()
            ->assertJsonPath('data.session.status', AiConversationSession::STATUS_COMPLETED)
            ->assertJsonPath('data.session.remaining_seconds', 0);

        $this->assertDatabaseHas('ai_conversation_sessions', [
            'id' => $session->id,
            'status' => AiConversationSession::STATUS_COMPLETED,
        ]);
    }

    public function test_learner_can_authorize_realtime_for_active_session(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->getJson("/api/ai-conversations/{$session->id}/realtime-authorize")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.id', $session->id)
            ->assertJsonPath('data.user.id', $learner->id)
            ->assertJsonPath('data.realtime.response_modality', 'AUDIO');
    }

    public function test_learner_cannot_authorize_realtime_for_expired_session(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now()->subMinutes(4),
            'expires_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($learner);

        $this->getJson("/api/ai-conversations/{$session->id}/realtime-authorize")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['session']);
    }

    public function test_learner_can_mark_session_as_expired(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->patchJson("/api/ai-conversations/{$session->id}/expire")
            ->assertOk()
            ->assertJsonPath('data.session.status', AiConversationSession::STATUS_EXPIRED);

        $this->assertDatabaseHas('ai_conversation_sessions', [
            'id' => $session->id,
            'status' => AiConversationSession::STATUS_EXPIRED,
        ]);
    }

    public function test_learner_can_store_and_list_conversation_messages(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/ai-conversations/{$session->id}/messages", [
            'role' => AiConversationMessage::ROLE_LEARNER,
            'content' => 'I went to school today.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.message.role', AiConversationMessage::ROLE_LEARNER)
            ->assertJsonPath('data.message.content', 'I went to school today.');

        $this->getJson("/api/ai-conversations/{$session->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.content', 'I went to school today.');
    }

    public function test_learner_can_send_conversation_turn_and_receive_agent_reply(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/ai-conversations/{$session->id}/turn", [
            'message' => 'Hello, I want to practice English.',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.learner_message.role', AiConversationMessage::ROLE_LEARNER)
            ->assertJsonPath('data.assistant_message.role', AiConversationMessage::ROLE_ASSISTANT)
            ->assertJsonStructure([
                'data' => [
                    'coach' => [
                        'reply',
                        'language',
                        'intent',
                        'task',
                        'fallback_used',
                        'gentle_correction',
                        'encouragement',
                        'next_question',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('ai_conversation_messages', [
            'ai_conversation_session_id' => $session->id,
            'role' => AiConversationMessage::ROLE_LEARNER,
            'content' => 'Hello, I want to practice English.',
        ]);

        $this->assertDatabaseHas('ai_conversation_messages', [
            'ai_conversation_session_id' => $session->id,
            'role' => AiConversationMessage::ROLE_ASSISTANT,
        ]);
    }

    public function test_specific_topic_request_rejects_generic_greeting_reply(): void
    {
        Config::set('ai.provider', 'gemini');
        Config::set('ai.gemini.api_key', 'test-gemini-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => "Bonjour! Comment puis-je t'aider aujourd'hui ?",
                                        'language' => 'fr-FR',
                                        'gentle_correction' => '',
                                        'encouragement' => 'Prends ton temps.',
                                        'next_question' => 'Comment puis-je t aider ?',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        $session->messages()->createMany([
            [
                'role' => AiConversationMessage::ROLE_LEARNER,
                'content' => 'Bonjour',
            ],
            [
                'role' => AiConversationMessage::ROLE_ASSISTANT,
                'content' => "Bonjour! Comment puis-je t'aider aujourd'hui ?",
            ],
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/ai-conversations/{$session->id}/turn", [
            'message' => "Parle-moi de l'alphabet",
        ])
            ->assertCreated()
            ->assertJsonPath('data.coach.intent', 'explainText')
            ->assertJsonPath('data.coach.fallback_used', true)
            ->assertJsonPath('data.coach.fallback_reason', 'repeated_previous_answer')
            ->assertJsonPath('data.assistant_message.content', "Je vais faire simple. l'alphabet est un sujet que je peux expliquer avec des mots faciles.");

        Http::assertSent(fn ($request): bool => str_contains(
            data_get($request->data(), 'contents.0.parts.0.text'),
            "Parle-moi de l'alphabet",
        ) && str_contains(
            data_get($request->data(), 'contents.0.parts.0.text'),
            'Intention detectee: explainText',
        ));
    }

    public function test_learner_can_assess_conversation_progress(): void
    {
        Config::set('ai.provider', 'fake');

        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(2),
            'expires_at' => now()->addMinute(),
            'ended_at' => now(),
        ]);

        $session->messages()->createMany([
            [
                'role' => AiConversationMessage::ROLE_ASSISTANT,
                'content' => 'Tell me about your day.',
            ],
            [
                'role' => AiConversationMessage::ROLE_LEARNER,
                'content' => 'I went to school today and I played with my friend after class.',
            ],
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/ai-conversations/{$session->id}/assess")
            ->assertOk()
            ->assertJsonPath('data.assessment.status', 'good')
            ->assertJsonPath('data.session.assessment_score', 78)
            ->assertJsonPath('data.assessment.feedback.title', 'Good conversation!');

        $this->assertDatabaseHas('ai_conversation_sessions', [
            'id' => $session->id,
            'assessment_score' => 78,
            'assessment_status' => 'good',
        ]);
    }

    public function test_conversation_assessment_requires_learner_message(): void
    {
        $learner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(2),
            'expires_at' => now()->addMinute(),
            'ended_at' => now(),
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/ai-conversations/{$session->id}/assess")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messages']);
    }

    public function test_learner_cannot_end_another_learners_session(): void
    {
        $learner = User::factory()->create();
        $otherLearner = User::factory()->create();
        $session = AiConversationSession::query()->create([
            'user_id' => $otherLearner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_ACTIVE,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        Sanctum::actingAs($learner);

        $this->patchJson("/api/ai-conversations/{$session->id}/end")
            ->assertForbidden();
    }

    public function test_ai_conversation_routes_require_learner_role(): void
    {
        $tutor = User::factory()->tutor()->create();

        Sanctum::actingAs($tutor);

        $this->postJson('/api/ai-conversations')->assertForbidden();
    }

    public function test_ai_conversation_routes_require_authentication(): void
    {
        $this->postJson('/api/ai-conversations')->assertUnauthorized();
    }
}
