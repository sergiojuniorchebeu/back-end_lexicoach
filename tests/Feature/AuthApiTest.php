<?php

namespace Tests\Feature;

use App\Models\LearnerAssociationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_a_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'test-client',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.full_name', 'Marie Dupont')
            ->assertJsonPath('data.user.email', 'marie@example.com')
            ->assertJsonPath('data.user.role', User::ROLE_LEARNER)
            ->assertJsonPath('data.user.conversation_limits.session_limit_seconds', 180)
            ->assertJsonPath('data.user.conversation_limits.daily_session_limit', 3)
            ->assertJsonPath('data.token.type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'token' => [
                        'access_token',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'role' => User::ROLE_LEARNER,
        ]);
    }

    public function test_tutor_can_register_from_mobile_app(): void
    {
        $learner = User::factory()->create();
        $associationCode = LearnerAssociationCode::factory()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-123456',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Teacher Paul',
            'email' => 'paul@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'test-client',
            'role' => User::ROLE_TUTOR,
            'association_code' => 'LC-123456',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.role', User::ROLE_TUTOR)
            ->assertJsonPath('data.linked_learner.id', $learner->id)
            ->assertJsonPath('data.token.type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'paul@example.com',
            'role' => User::ROLE_TUTOR,
        ]);

        $this->assertDatabaseHas('tutor_learners', [
            'tutor_id' => User::query()->where('email', 'paul@example.com')->firstOrFail()->id,
            'learner_id' => $learner->id,
        ]);
        $this->assertNotNull($associationCode->fresh()?->used_at);
    }

    public function test_tutor_registration_requires_association_code(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name' => 'Teacher Paul',
            'email' => 'paul@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'test-client',
            'role' => User::ROLE_TUTOR,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['association_code']);
    }

    public function test_admin_cannot_register_from_public_auth_route(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_ADMIN,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_user_can_login_and_receive_a_sanctum_token(): void
    {
        User::factory()->create([
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'marie@example.com',
            'password' => 'password123',
            'device_name' => 'test-client',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.full_name', 'Marie Dupont')
            ->assertJsonPath('data.user.role', User::ROLE_LEARNER)
            ->assertJsonPath('data.user.conversation_limits.session_limit_seconds', 180)
            ->assertJsonPath('data.user.conversation_limits.daily_session_limit', 3)
            ->assertJsonPath('data.token.type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'token' => [
                        'access_token',
                    ],
                ],
            ]);
    }

    public function test_invalid_login_returns_validation_error(): void
    {
        User::factory()->create([
            'email' => 'marie@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'marie@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
        ]);

        $token = $user->createToken('test-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'marie@example.com')
            ->assertJsonPath('data.user.role', User::ROLE_LEARNER)
            ->assertJsonPath('data.user.conversation_limits.session_limit_seconds', 180)
            ->assertJsonPath('data.user.conversation_limits.session_limit_minutes', 3)
            ->assertJsonPath('data.user.conversation_limits.daily_session_limit', 3);
    }

    public function test_authenticated_user_can_update_profile_preferences(): void
    {
        $user = User::factory()->create([
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
        ]);

        $token = $user->createToken('test-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/auth/profile', [
                'full_name' => 'Marie Updated',
                'email' => 'marie.updated@example.com',
                'preferred_language' => 'en',
                'learning_level' => 'intermediate',
                'dyslexia_font_size' => 20,
                'dyslexia_slow_speech' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.full_name', 'Marie Updated')
            ->assertJsonPath('data.user.email', 'marie.updated@example.com')
            ->assertJsonPath('data.user.preferences.learning_level', 'intermediate')
            ->assertJsonPath('data.user.preferences.dyslexia_font_size', 20)
            ->assertJsonPath('data.user.preferences.dyslexia_slow_speech', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Marie Updated',
            'email' => 'marie.updated@example.com',
            'learning_level' => 'intermediate',
            'dyslexia_font_size' => 20,
        ]);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = $user->createToken('test-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/auth/password', [
                'current_password' => 'password123',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ])->assertOk();
    }

    public function test_authenticated_user_can_logout_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_api_protected_routes_do_not_redirect_to_web_login(): void
    {
        $this->get('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_api_documentation_page_shows_auth_endpoints_and_json_examples(): void
    {
        $this->get('/api/docs')
            ->assertOk()
            ->assertSeeText('API Documentation')
            ->assertSeeText('/api/auth/register')
            ->assertSeeText('/api/auth/login')
            ->assertSeeText('/api/auth/me')
            ->assertSeeText('/api/auth/logout')
            ->assertSeeText('/api/me/reading-attempts')
            ->assertSeeText('/api/me/reading-progress')
            ->assertSeeText('/api/me/association-code')
            ->assertSeeText('/api/me/association-code/regenerate')
            ->assertSeeText('/api/tutor/dashboard')
            ->assertSeeText('/api/tutor/learners/link')
            ->assertSeeText('/api/tutor/learners/{learner}/progress')
            ->assertSeeText('/api/tutor/learners/{learner}')
            ->assertSeeText('/api/admin/dashboard')
            ->assertSeeText('/api/admin/users')
            ->assertSeeText('/api/admin/users/{user}/role')
            ->assertSeeText('"success": true')
            ->assertSeeText('"role": "learner"')
            ->assertSeeText('"access_token": "1|sampleSanctumToken"')
            ->assertSeeText('Scenarios and Frontend Role')
            ->assertSeeText('Scenario 7 - Reading Evaluation')
            ->assertSeeText('Scenario 11 - Tutor Reviews Learner Progress')
            ->assertSeeText('Scenario 14 - Admin Uses Separate Login and Dashboard')
            ->assertSeeText('/api/learning-modes')
            ->assertSeeText('/api/me/progress')
            ->assertSeeText('Flutter Examples')
            ->assertSeeText('Copy docs as .md')
            ->assertSee('# API Documentation')
            ->assertSee('## Flutter Examples')
            ->assertSeeText("const String baseUrl = 'https://lexicoach.mrsergio.dev/api';")
            ->assertSeeText('class AuthApiService');
    }
}
