<?php

namespace Tests\Feature;

use App\Models\LearnerAssociationCode;
use App\Models\LearningMode;
use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TutorLearnerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_can_generate_an_association_code(): void
    {
        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson('/api/me/association-code')
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'association_code' => [
                        'id',
                        'code',
                        'expires_at',
                        'used_at',
                        'cancelled_at',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('learner_association_codes', [
            'learner_id' => $learner->id,
            'used_at' => null,
        ]);
    }

    public function test_learner_can_get_active_association_code(): void
    {
        $learner = User::factory()->create();
        LearnerAssociationCode::factory()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-123456',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/association-code')
            ->assertOk()
            ->assertJsonPath('data.association_code.code', 'LC-123456');
    }

    public function test_learner_can_see_linked_tutors_from_association_code_endpoint(): void
    {
        $learner = User::factory()->create();
        $tutor = User::factory()->tutor()->create([
            'name' => 'Teacher Paul',
            'email' => 'teacher@example.com',
        ]);

        $tutor->learners()->attach($learner->id);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/association-code')
            ->assertOk()
            ->assertJsonPath('data.association_code', null)
            ->assertJsonCount(1, 'data.linked_tutors')
            ->assertJsonPath('data.linked_tutors.0.full_name', 'Teacher Paul')
            ->assertJsonPath('data.linked_tutors.0.email', 'teacher@example.com')
            ->assertJsonPath('data.linked_tutors.0.role', User::ROLE_TUTOR);
    }

    public function test_tutor_can_link_learner_with_valid_association_code(): void
    {
        $learner = User::factory()->create([
            'name' => 'Marie Dupont',
        ]);
        $tutor = User::factory()->tutor()->create();
        $associationCode = LearnerAssociationCode::factory()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-123456',
        ]);

        Sanctum::actingAs($tutor);

        $this->postJson('/api/tutor/learners/link', [
            'code' => 'LC-123456',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.learner.full_name', 'Marie Dupont')
            ->assertJsonPath('data.learner.role', User::ROLE_LEARNER);

        $this->assertDatabaseHas('tutor_learners', [
            'tutor_id' => $tutor->id,
            'learner_id' => $learner->id,
        ]);

        $this->assertNotNull($associationCode->fresh()?->used_at);
    }

    public function test_tutor_cannot_link_with_expired_or_used_code(): void
    {
        $learner = User::factory()->create();
        $tutor = User::factory()->tutor()->create();

        LearnerAssociationCode::factory()->expired()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-111111',
        ]);

        LearnerAssociationCode::factory()->used()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-222222',
        ]);

        Sanctum::actingAs($tutor);

        $this->postJson('/api/tutor/learners/link', [
            'code' => 'LC-111111',
        ])->assertUnprocessable();

        $this->postJson('/api/tutor/learners/link', [
            'code' => 'LC-222222',
        ])->assertUnprocessable();
    }

    public function test_learner_can_cancel_active_association_code(): void
    {
        $learner = User::factory()->create();
        LearnerAssociationCode::factory()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-123456',
        ]);

        Sanctum::actingAs($learner);

        $this->deleteJson('/api/me/association-code')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.association_code.code', 'LC-123456');

        $this->assertNotNull(
            LearnerAssociationCode::query()->where('code', 'LC-123456')->first()?->cancelled_at
        );
    }

    public function test_learner_can_regenerate_association_code(): void
    {
        $learner = User::factory()->create();
        LearnerAssociationCode::factory()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-123456',
        ]);

        Sanctum::actingAs($learner);

        $this->postJson('/api/me/association-code/regenerate')
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.association_code.used_at', null)
            ->assertJsonPath('data.association_code.cancelled_at', null);

        $this->assertDatabaseCount('learner_association_codes', 2);
        $this->assertNotNull(
            LearnerAssociationCode::query()->where('code', 'LC-123456')->first()?->cancelled_at
        );
    }

    public function test_tutor_cannot_link_with_cancelled_code(): void
    {
        $learner = User::factory()->create();
        $tutor = User::factory()->tutor()->create();

        LearnerAssociationCode::factory()->cancelled()->create([
            'learner_id' => $learner->id,
            'code' => 'LC-333333',
        ]);

        Sanctum::actingAs($tutor);

        $this->postJson('/api/tutor/learners/link', [
            'code' => 'LC-333333',
        ])->assertUnprocessable();
    }

    public function test_tutor_can_list_linked_learners(): void
    {
        $tutor = User::factory()->tutor()->create();
        $linkedLearner = User::factory()->create([
            'name' => 'Marie Dupont',
        ]);
        User::factory()->create([
            'name' => 'Other Learner',
        ]);

        $tutor->learners()->attach($linkedLearner->id);

        Sanctum::actingAs($tutor);

        $this->getJson('/api/tutor/learners')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.learners')
            ->assertJsonPath('data.learners.0.full_name', 'Marie Dupont');
    }

    public function test_tutor_can_view_linked_learner_progress(): void
    {
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create([
            'name' => 'Marie Dupont',
        ]);
        LearningMode::factory()->create([
            'name' => 'Writing Assistant',
            'slug' => LearningMode::SLUG_WRITING,
            'sort_order' => 2,
        ]);
        $exercise = ReadingExercise::factory()->create();

        $tutor->learners()->attach($learner->id);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 100,
            'status' => 'excellent',
            'is_correct' => true,
        ]);

        Sanctum::actingAs($tutor);

        $this->getJson("/api/tutor/learners/{$learner->id}/progress")
            ->assertOk()
            ->assertJsonPath('data.learner.full_name', 'Marie Dupont')
            ->assertJsonPath('data.progress.reading.summary.total_attempts', 1)
            ->assertJsonPath('data.progress.reading.summary.average_score', 100)
            ->assertJsonPath('data.progress.writing.summary.total_attempts', 0);
    }

    public function test_tutor_can_view_dashboard_summary(): void
    {
        $tutor = User::factory()->tutor()->create();
        $activeLearner = User::factory()->create([
            'name' => 'Marie Active',
        ]);
        $quietLearner = User::factory()->create([
            'name' => 'Anne Quiet',
        ]);
        LearningMode::factory()->create([
            'name' => 'Writing Assistant',
            'slug' => LearningMode::SLUG_WRITING,
            'sort_order' => 2,
        ]);
        $exercise = ReadingExercise::factory()->create();

        $tutor->learners()->attach([$activeLearner->id, $quietLearner->id]);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $activeLearner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 60,
        ]);

        Sanctum::actingAs($tutor);

        $this->getJson('/api/tutor/dashboard')
            ->assertOk()
            ->assertJsonPath('data.dashboard.total_learners', 2)
            ->assertJsonPath('data.dashboard.active_learners', 1)
            ->assertJsonPath('data.dashboard.reading.total_attempts', 1)
            ->assertJsonPath('data.dashboard.reading.average_score', 60)
            ->assertJsonCount(2, 'data.dashboard.reading.learners_needing_attention')
            ->assertJsonPath('data.dashboard.progress_by_mode.reading.summary.total_attempts', 1)
            ->assertJsonPath('data.dashboard.progress_by_mode.writing.summary.total_attempts', 0);
    }

    public function test_tutor_can_view_linked_learner_reading_attempts(): void
    {
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'title' => 'Museum visit',
        ]);

        $tutor->learners()->attach($learner->id);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 90,
        ]);

        Sanctum::actingAs($tutor);

        $this->getJson("/api/tutor/learners/{$learner->id}/reading-attempts")
            ->assertOk()
            ->assertJsonPath('data.attempts.0.exercise.title', 'Museum visit')
            ->assertJsonPath('data.attempts.0.score', 90);
    }

    public function test_tutor_cannot_view_unlinked_learner_progress(): void
    {
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create();

        Sanctum::actingAs($tutor);

        $this->getJson("/api/tutor/learners/{$learner->id}/progress")
            ->assertForbidden();
    }

    public function test_tutor_can_detach_linked_learner(): void
    {
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create();

        $tutor->learners()->attach($learner->id);

        Sanctum::actingAs($tutor);

        $this->deleteJson("/api/tutor/learners/{$learner->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tutor_learners', [
            'tutor_id' => $tutor->id,
            'learner_id' => $learner->id,
        ]);
    }

    public function test_learner_cannot_access_tutor_routes(): void
    {
        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->getJson('/api/tutor/learners')->assertForbidden();
        $this->getJson('/api/tutor/dashboard')->assertForbidden();
        $this->postJson('/api/tutor/learners/link', [
            'code' => 'LC-123456',
        ])->assertForbidden();
    }

    public function test_tutor_cannot_generate_learner_association_code(): void
    {
        $tutor = User::factory()->tutor()->create();

        Sanctum::actingAs($tutor);

        $this->postJson('/api/me/association-code')->assertForbidden();
        $this->getJson('/api/me/association-code')->assertForbidden();
    }
}
