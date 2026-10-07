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
            ->assertJsonPath('data.user.status', User::STATUS_ACTIVE);

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

    public function test_admin_can_suspend_and_reactivate_user(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create();
        $learner->createToken('mobile');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => User::STATUS_SUSPENDED,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.status', User::STATUS_SUSPENDED);

        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'status' => User::STATUS_SUSPENDED,
        ]);

        // Un token deja emis avant la suspension est revoque.
        $this->assertSame(0, $learner->tokens()->count());

        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => User::STATUS_ACTIVE,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.status', User::STATUS_ACTIVE);
    }

    public function test_admin_can_block_and_reactivate_user(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create();
        $learner->createToken('mobile');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => User::STATUS_BLOCKED,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.status', User::STATUS_BLOCKED);

        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'status' => User::STATUS_BLOCKED,
        ]);

        // Un token deja emis avant le blocage est revoque, comme pour une suspension.
        $this->assertSame(0, $learner->tokens()->count());

        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => User::STATUS_ACTIVE,
        ])
            ->assertOk()
            ->assertJsonPath('data.user.status', User::STATUS_ACTIVE);
    }

    public function test_blocked_status_blocks_requests_even_with_a_still_valid_token(): void
    {
        $learner = User::factory()->create();
        $token = $learner->createToken('mobile')->plainTextToken;

        $learner->update(['status' => User::STATUS_BLOCKED]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }

    public function test_blocked_user_cannot_login(): void
    {
        $learner = User::factory()->create([
            'status' => User::STATUS_BLOCKED,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $learner->email,
            'password' => 'password',
        ])
            ->assertForbidden();
    }

    public function test_admin_cannot_block_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/status", [
            'status' => User::STATUS_BLOCKED,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_suspended_status_blocks_requests_even_with_a_still_valid_token(): void
    {
        $learner = User::factory()->create();
        $token = $learner->createToken('mobile')->plainTextToken;

        // Suspension directe (sans passer par l'admin, donc sans revocation
        // de token) : c'est EnsureAccountIsActive seul qui doit bloquer.
        $learner->update(['status' => User::STATUS_SUSPENDED]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }

    public function test_suspended_user_cannot_login(): void
    {
        $learner = User::factory()->create([
            'status' => User::STATUS_SUSPENDED,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $learner->email,
            'password' => 'password',
        ])
            ->assertForbidden();
    }

    public function test_admin_cannot_suspend_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/status", [
            'status' => User::STATUS_SUSPENDED,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_status_update_is_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => 'banned',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
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
        $this->patchJson("/api/admin/users/{$learner->id}/status", [
            'status' => User::STATUS_SUSPENDED,
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
