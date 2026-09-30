<?php

namespace Tests\Feature;

use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReadingExerciseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_active_reading_exercises(): void
    {
        $user = User::factory()->create();

        ReadingExercise::factory()->create([
            'title' => 'Museum visit',
            'text' => 'The children visited the beautiful museum yesterday.',
            'sort_order' => 2,
        ]);

        ReadingExercise::factory()->create([
            'title' => 'Inactive exercise',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/reading-exercises')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.exercises')
            ->assertJsonPath('data.exercises.0.title', 'Museum visit');
    }

    public function test_authenticated_user_can_get_one_reading_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'title' => 'Garden story',
            'text' => 'The little boy is playing in the garden.',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/reading-exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('data.exercise.title', 'Garden story')
            ->assertJsonPath('data.exercise.language', 'en-US');
    }

    public function test_reading_exercise_evaluation_returns_perfect_score_when_transcript_matches(): void
    {
        $user = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'text' => 'The little boy is playing in the garden.',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/reading-exercises/{$exercise->id}/evaluate", [
            'transcript' => 'The little boy is playing in the garden.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.score', 100)
            ->assertJsonPath('data.result.status', 'excellent')
            ->assertJsonPath('data.result.is_correct', true)
            ->assertJsonPath('data.result.feedback.title', 'Excellent!')
            ->assertJsonPath('data.result.words.0.status', 'correct')
            ->assertJsonPath('data.attempt.score', 100)
            ->assertJsonPath('data.attempt.is_correct', true);

        $this->assertDatabaseHas('reading_exercise_attempts', [
            'user_id' => $user->id,
            'reading_exercise_id' => $exercise->id,
            'transcript' => 'The little boy is playing in the garden.',
            'score' => 100,
            'status' => 'excellent',
            'is_correct' => true,
        ]);
    }

    public function test_reading_exercise_evaluation_detects_missing_and_incorrect_words(): void
    {
        $user = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'text' => 'The little boy is playing in the garden.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/reading-exercises/{$exercise->id}/evaluate", [
            'transcript' => 'The little boy playing on garden.',
        ])
            ->assertOk()
            ->assertJsonPath('data.result.is_correct', false);

        $statuses = collect($response->json('data.result.words'))->pluck('status')->all();

        $this->assertContains('missing', $statuses);
        $this->assertContains('incorrect', $statuses);
    }

    public function test_reading_exercise_evaluation_requires_a_transcript(): void
    {
        $user = User::factory()->create();
        $exercise = ReadingExercise::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/reading-exercises/{$exercise->id}/evaluate", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['transcript']);
    }

    public function test_reading_exercise_routes_require_authentication(): void
    {
        $exercise = ReadingExercise::factory()->create();

        $this->getJson('/api/reading-exercises')->assertUnauthorized();
        $this->getJson("/api/reading-exercises/{$exercise->id}")->assertUnauthorized();
        $this->postJson("/api/reading-exercises/{$exercise->id}/evaluate", [
            'transcript' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_reading_exercise_routes_require_learner_role(): void
    {
        $tutor = User::factory()->tutor()->create();
        $exercise = ReadingExercise::factory()->create();

        Sanctum::actingAs($tutor);

        $this->getJson('/api/reading-exercises')->assertForbidden();
        $this->getJson("/api/reading-exercises/{$exercise->id}")->assertForbidden();
        $this->postJson("/api/reading-exercises/{$exercise->id}/evaluate", [
            'transcript' => 'Hello',
        ])->assertForbidden();
        $this->getJson('/api/me/reading-attempts')->assertForbidden();
        $this->getJson('/api/me/reading-progress')->assertForbidden();
    }

    public function test_learner_can_list_only_their_reading_attempts(): void
    {
        $learner = User::factory()->create();
        $otherLearner = User::factory()->create();
        $exercise = ReadingExercise::factory()->create([
            'title' => 'Museum visit',
        ]);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 90,
        ]);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $otherLearner->id,
            'reading_exercise_id' => $exercise->id,
            'score' => 40,
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/reading-attempts')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.attempts')
            ->assertJsonPath('data.attempts.0.exercise.title', 'Museum visit')
            ->assertJsonPath('data.attempts.0.score', 90);
    }

    public function test_learner_can_get_reading_progress_summary(): void
    {
        $learner = User::factory()->create();
        $firstExercise = ReadingExercise::factory()->create();
        $secondExercise = ReadingExercise::factory()->create();

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $firstExercise->id,
            'score' => 80,
            'is_correct' => false,
            'created_at' => now()->subDay(),
        ]);

        ReadingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'reading_exercise_id' => $secondExercise->id,
            'score' => 100,
            'status' => 'excellent',
            'is_correct' => true,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/reading-progress')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.progress.total_attempts', 2)
            ->assertJsonPath('data.progress.completed_exercises', 1)
            ->assertJsonPath('data.progress.average_score', 90)
            ->assertJsonPath('data.progress.best_score', 100)
            ->assertJsonPath('data.progress.latest_attempt.score', 100);
    }

    public function test_reading_progress_routes_require_authentication(): void
    {
        $this->getJson('/api/me/reading-attempts')->assertUnauthorized();
        $this->getJson('/api/me/reading-progress')->assertUnauthorized();
    }
}
