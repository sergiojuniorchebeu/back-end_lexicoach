<?php

namespace Tests\Feature;

use App\Models\LearningMode;
use App\Models\SmartAbstractAttempt;
use App\Models\SmartAbstractExercise;
use App\Models\User;
use App\Models\WritingExercise;
use App\Models\WritingExerciseAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WritingSmartAbstractApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.provider', 'fake');
    }

    public function test_learner_can_list_and_evaluate_writing_exercise(): void
    {
        $learner = User::factory()->create();
        $exercise = WritingExercise::factory()->create([
            'title' => 'My school day',
            'prompt' => 'Write five sentences about your school day.',
            'min_words' => 8,
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/writing-exercises')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.exercises')
            ->assertJsonPath('data.exercises.0.title', 'My school day');

        $this->postJson("/api/writing-exercises/{$exercise->id}/evaluate", [
            'answer' => 'Today I went to school and learned a new English word with my teacher.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.status', 'good')
            ->assertJsonPath('data.result.score', 82)
            ->assertJsonPath('data.result.feedback.title', 'Good work!')
            ->assertJsonPath('data.attempt.score', 82);

        $this->assertDatabaseHas('writing_exercise_attempts', [
            'user_id' => $learner->id,
            'writing_exercise_id' => $exercise->id,
            'score' => 82,
            'status' => 'good',
        ]);
    }

    public function test_learner_can_list_and_evaluate_smart_abstract_exercise(): void
    {
        $learner = User::factory()->create();
        $exercise = SmartAbstractExercise::factory()->create([
            'title' => 'Library paragraph',
            'source_text' => 'Every Wednesday, the class visits the library. The teacher helps each learner choose one book.',
            'min_words' => 8,
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/smart-abstract-exercises')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.exercises')
            ->assertJsonPath('data.exercises.0.title', 'Library paragraph');

        $this->postJson("/api/smart-abstract-exercises/{$exercise->id}/evaluate", [
            'document_text' => 'Every Wednesday, the class visits the library. The teacher helps each learner choose one book. After reading quietly, the learners share one new word.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.status', 'good')
            ->assertJsonPath('data.result.score', 82)
            ->assertJsonPath('data.result.feedback.title', 'Summary ready!')
            ->assertJsonPath('data.attempt.score', 82);

        $this->assertDatabaseHas('smart_abstract_attempts', [
            'user_id' => $learner->id,
            'smart_abstract_exercise_id' => $exercise->id,
            'score' => 82,
            'status' => 'good',
        ]);
    }

    public function test_writing_and_smart_abstract_validation_requires_text(): void
    {
        $learner = User::factory()->create();
        $writingExercise = WritingExercise::factory()->create();
        $smartAbstractExercise = SmartAbstractExercise::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson("/api/writing-exercises/{$writingExercise->id}/evaluate", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answer']);

        $this->postJson("/api/smart-abstract-exercises/{$smartAbstractExercise->id}/evaluate", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_text']);
    }

    public function test_learner_can_split_words_into_syllables(): void
    {
        $learner = User::factory()->create();

        Sanctum::actingAs($learner);

        $this->postJson('/api/word-splitting/split', [
            'text' => 'beautiful garden',
            'language' => 'en-US',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.words.0.clean_word', 'beautiful')
            ->assertJsonPath('data.result.words.1.clean_word', 'garden')
            ->assertJsonCount(2, 'data.result.words');
    }

    public function test_learning_mode_exercises_endpoint_supports_writing_and_smart_abstract(): void
    {
        $learner = User::factory()->create();
        $writingMode = LearningMode::factory()->create([
            'name' => 'Writing Assistant',
            'slug' => LearningMode::SLUG_WRITING,
            'sort_order' => 2,
        ]);
        $smartAbstractMode = LearningMode::factory()->create([
            'name' => 'Smart Abstract',
            'slug' => LearningMode::SLUG_SMART_ABSTRACT,
            'sort_order' => 5,
        ]);

        WritingExercise::factory()->create([
            'learning_mode_id' => $writingMode->id,
            'title' => 'My school day',
        ]);
        SmartAbstractExercise::factory()->create([
            'learning_mode_id' => $smartAbstractMode->id,
            'title' => 'Library paragraph',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/learning-modes/writing/exercises')
            ->assertOk()
            ->assertJsonPath('data.learning_mode.slug', LearningMode::SLUG_WRITING)
            ->assertJsonPath('data.exercises.0.title', 'My school day');

        $this->getJson('/api/learning-modes/smart-abstract/exercises')
            ->assertOk()
            ->assertJsonPath('data.learning_mode.slug', LearningMode::SLUG_SMART_ABSTRACT)
            ->assertJsonPath('data.exercises.0.title', 'Library paragraph');
    }

    public function test_global_progress_includes_writing_and_smart_abstract_attempts(): void
    {
        $learner = User::factory()->create();
        LearningMode::factory()->create([
            'name' => 'Writing Assistant',
            'slug' => LearningMode::SLUG_WRITING,
            'sort_order' => 2,
        ]);
        LearningMode::factory()->create([
            'name' => 'Smart Abstract',
            'slug' => LearningMode::SLUG_SMART_ABSTRACT,
            'sort_order' => 5,
        ]);

        WritingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'score' => 80,
            'status' => 'good',
        ]);
        SmartAbstractAttempt::factory()->create([
            'user_id' => $learner->id,
            'score' => 90,
            'status' => 'excellent',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson('/api/me/progress')
            ->assertOk()
            ->assertJsonPath('data.progress.writing.summary.total_attempts', 1)
            ->assertJsonPath('data.progress.writing.summary.average_score', 80)
            ->assertJsonPath('data.progress.smart-abstract.summary.total_attempts', 1)
            ->assertJsonPath('data.progress.smart-abstract.summary.average_score', 90);
    }

    public function test_tutor_can_view_linked_learner_ai_attempts(): void
    {
        $tutor = User::factory()->tutor()->create();
        $learner = User::factory()->create();
        $tutor->learners()->attach($learner->id);

        WritingExerciseAttempt::factory()->create([
            'user_id' => $learner->id,
            'score' => 81,
        ]);
        SmartAbstractAttempt::factory()->create([
            'user_id' => $learner->id,
            'score' => 83,
        ]);

        Sanctum::actingAs($tutor);

        $this->getJson("/api/tutor/learners/{$learner->id}/writing-attempts")
            ->assertOk()
            ->assertJsonPath('data.attempts.0.score', 81);

        $this->getJson("/api/tutor/learners/{$learner->id}/smart-abstract-attempts")
            ->assertOk()
            ->assertJsonPath('data.attempts.0.score', 83);
    }

    public function test_ai_exercise_routes_require_learner_role(): void
    {
        $tutor = User::factory()->tutor()->create();
        $writingExercise = WritingExercise::factory()->create();
        $smartAbstractExercise = SmartAbstractExercise::factory()->create();

        Sanctum::actingAs($tutor);

        $this->getJson('/api/writing-exercises')->assertForbidden();
        $this->postJson("/api/writing-exercises/{$writingExercise->id}/evaluate", [
            'answer' => 'This is a long enough answer for validation.',
        ])->assertForbidden();
        $this->getJson('/api/smart-abstract-exercises')->assertForbidden();
        $this->postJson("/api/smart-abstract-exercises/{$smartAbstractExercise->id}/evaluate", [
            'summary' => 'This is a long enough summary for validation.',
        ])->assertForbidden();
    }

    public function test_writing_evaluation_can_use_gemini_provider(): void
    {
        Config::set('ai.provider', 'gemini');
        Config::set('ai.gemini.api_key', 'test-gemini-key');
        Config::set('ai.gemini.model', 'gemini-3.5-flash-lite');

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'score' => 91,
                                        'status' => 'excellent',
                                        'corrected_text' => 'Today I went to school and learned a new English word.',
                                        'mistakes' => [],
                                        'suggestions' => ['Keep writing full sentences.'],
                                        'feedback' => [
                                            'title' => 'Excellent work!',
                                            'message' => 'The answer is clear and correct.',
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $learner = User::factory()->create();
        $exercise = WritingExercise::factory()->create([
            'min_words' => 8,
        ]);

        Sanctum::actingAs($learner);

        $this->postJson("/api/writing-exercises/{$exercise->id}/evaluate", [
            'answer' => 'Today I went to school and learned a new English word.',
        ])
            ->assertOk()
            ->assertJsonPath('data.result.score', 91)
            ->assertJsonPath('data.result.status', 'excellent')
            ->assertJsonPath('data.result.feedback.title', 'Excellent work!');

        Http::assertSent(fn ($request): bool => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && str_contains($request->url(), 'models/gemini-3.5-flash-lite:generateContent'));
    }
}
