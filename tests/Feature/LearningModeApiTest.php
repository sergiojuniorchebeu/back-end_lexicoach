<?php

namespace Tests\Feature;

use App\Models\AiConversationSession;
use App\Models\LearningMode;
use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearningModeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_can_list_active_learning_modes(): void
    {
        $learner = User::factory()->create();

        LearningMode::factory()->reading()->create();
        LearningMode::factory()->create([
            'name' => 'Hidden mode',
            'slug' => 'hidden-mode',
            'is_active' => false,
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/learning-modes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.learning_modes')
            ->assertJsonPath('data.learning_modes.0.slug', LearningMode::SLUG_READING);
    }

    public function test_learner_can_get_one_learning_mode_by_slug(): void
    {
        $learner = User::factory()->create();
        LearningMode::factory()->reading()->create();

        Sanctum::actingAs($learner);

        $this->getJson('/api/learning-modes/reading')
            ->assertOk()
            ->assertJsonPath('data.learning_mode.name', 'Reading Practice')
            ->assertJsonPath('data.learning_mode.slug', LearningMode::SLUG_READING);
    }

    public function test_learner_can_get_exercises_for_reading_mode(): void
    {
        $learner = User::factory()->create();
        $readingMode = LearningMode::factory()->reading()->create();

        ReadingExercise::factory()->create([
            'learning_mode_id' => $readingMode->id,
            'title' => 'Museum visit',
            'sort_order' => 1,
        ]);

        ReadingExercise::factory()->create([
            'learning_mode_id' => $readingMode->id,
            'title' => 'Inactive exercise',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/learning-modes/reading/exercises')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.learning_mode.slug', LearningMode::SLUG_READING)
            ->assertJsonCount(1, 'data.exercises')
            ->assertJsonPath('data.exercises.0.title', 'Museum visit');
    }

    public function test_global_progress_returns_reading_summary_and_empty_future_modes(): void
    {
        $learner = User::factory()->create();
        $readingMode = LearningMode::factory()->reading()->create();
        LearningMode::factory()->create([
            'name' => 'Writing Assistant',
            'slug' => LearningMode::SLUG_WRITING,
            'sort_order' => 2,
        ]);
        $exercise = ReadingExercise::factory()->create([
            'learning_mode_id' => $readingMode->id,
        ]);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 100,
            'status' => 'excellent',
            'is_correct' => true,
        ]);

        AiConversationSession::query()->create([
            'user_id' => $learner->id,
            'session_limit_seconds' => 180,
            'status' => AiConversationSession::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinutes(2),
            'ended_at' => now()->subMinutes(2),
            'assessment_score' => 78,
            'assessment_status' => 'good',
            'assessment_feedback' => [
                'feedback' => [
                    'title' => 'Good conversation!',
                    'message' => 'Clear enough.',
                ],
            ],
            'assessed_at' => now(),
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/progress')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.progress.reading.learning_mode.slug', LearningMode::SLUG_READING)
            ->assertJsonPath('data.progress.reading.summary.total_attempts', 1)
            ->assertJsonPath('data.progress.reading.summary.average_score', 100)
            ->assertJsonPath('data.progress.writing.learning_mode.slug', LearningMode::SLUG_WRITING)
            ->assertJsonPath('data.progress.writing.summary.total_attempts', 0)
            ->assertJsonPath('data.progress.ai-conversation.learning_mode.slug', 'ai-conversation')
            ->assertJsonPath('data.progress.ai-conversation.summary.total_sessions', 1)
            ->assertJsonPath('data.progress.ai-conversation.summary.average_score', 78);
    }

    public function test_learning_mode_routes_require_learner_role(): void
    {
        $tutor = User::factory()->tutor()->create();
        LearningMode::factory()->reading()->create();

        Sanctum::actingAs($tutor);

        $this->getJson('/api/learning-modes')->assertForbidden();
        $this->getJson('/api/learning-modes/reading')->assertForbidden();
        $this->getJson('/api/learning-modes/reading/exercises')->assertForbidden();
        $this->getJson('/api/me/progress')->assertForbidden();
    }

    public function test_learning_mode_routes_require_authentication(): void
    {
        $this->getJson('/api/learning-modes')->assertUnauthorized();
        $this->getJson('/api/learning-modes/reading')->assertUnauthorized();
        $this->getJson('/api/learning-modes/reading/exercises')->assertUnauthorized();
        $this->getJson('/api/me/progress')->assertUnauthorized();
    }
}
