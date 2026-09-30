<?php

namespace Tests\Feature;

use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'title' => 'Garden sentence',
        ]);

        $tutor->learners()->attach($learner->id);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 80,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dashboard.users.total', 3)
            ->assertJsonPath('data.dashboard.users.learners', 1)
            ->assertJsonPath('data.dashboard.users.tutors', 1)
            ->assertJsonPath('data.dashboard.users.admins', 1)
            ->assertJsonPath('data.dashboard.learning.reading_attempts', 1)
            ->assertJsonPath('data.dashboard.learning.average_reading_score', 80)
            ->assertJsonPath('data.dashboard.tutor_view.linked_pairs', 1)
            ->assertJsonPath('data.dashboard.recent_attempts.0.exercise.title', 'Garden sentence');
    }

    public function test_admin_can_list_and_filter_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'Marie Learner',
            'email' => 'marie@example.com',
        ]);
        User::factory()->tutor()->create([
            'name' => 'Paul Tutor',
            'email' => 'paul@example.com',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?role=tutor')
            ->assertOk()
            ->assertJsonCount(1, 'data.users')
            ->assertJsonPath('data.users.0.full_name', 'Paul Tutor');

        $this->getJson('/api/admin/users?search=marie')
            ->assertOk()
            ->assertJsonCount(1, 'data.users')
            ->assertJsonPath('data.users.0.email', 'marie@example.com');
    }

    public function test_admin_can_show_user_and_update_role(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create([
            'name' => 'Marie Learner',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/users/{$learner->id}")
            ->assertOk()
            ->assertJsonPath('data.user.full_name', 'Marie Learner')
            ->assertJsonPath('data.user.role', User::ROLE_LEARNER)
            ->assertJsonPath('data.user.conversation_limits.session_limit_seconds', 180)
            ->assertJsonPath('data.user.conversation_limits.session_limit_minutes', 3)
            ->assertJsonPath('data.user.conversation_limits.daily_session_limit', 3);

        $this->patchJson("/api/admin/users/{$learner->id}/role", [
            'role' => User::ROLE_TUTOR,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', User::ROLE_TUTOR);

        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'role' => User::ROLE_TUTOR,
        ]);
    }

    public function test_admin_can_update_user_conversation_limits(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$learner->id}/conversation-limits", [
            'ai_conversation_session_limit_seconds' => 300,
            'ai_conversation_daily_session_limit' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.conversation_limits.session_limit_seconds', 300)
            ->assertJsonPath('data.user.conversation_limits.session_limit_minutes', 5)
            ->assertJsonPath('data.user.conversation_limits.daily_session_limit', 5);

        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'ai_conversation_session_limit_seconds' => 300,
            'ai_conversation_daily_session_limit' => 5,
        ]);
    }

    public function test_admin_conversation_limits_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$learner->id}/conversation-limits", [
            'ai_conversation_session_limit_seconds' => 30,
            'ai_conversation_daily_session_limit' => 101,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ai_conversation_session_limit_seconds',
                'ai_conversation_daily_session_limit',
            ]);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/role", [
            'role' => User::ROLE_LEARNER,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $learner = User::factory()->create();
        $tutor = User::factory()->tutor()->create();

        Sanctum::actingAs($learner);
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        Sanctum::actingAs($tutor);
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->patchJson("/api/admin/users/{$learner->id}/conversation-limits", [
            'ai_conversation_session_limit_seconds' => 300,
            'ai_conversation_daily_session_limit' => 5,
        ])->assertForbidden();
    }

    public function test_admin_login_and_dashboard_pages_load(): void
    {
        $this->withoutVite();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin LexiCoach')
            ->assertSee('Se connecter');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('/admin/login');
    }
}
