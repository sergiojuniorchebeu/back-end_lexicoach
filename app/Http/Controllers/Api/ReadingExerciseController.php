<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateReadingExerciseRequest;
use App\Models\ReadingExercise;
use App\Models\ReadingExerciseAttempt;
use App\Models\User;
use App\Services\ReadingEvaluator;
use Illuminate\Http\JsonResponse;

class ReadingExerciseController extends Controller
{
    public function __construct(
        private readonly ReadingEvaluator $readingEvaluator,
    ) {}

    public function index(): JsonResponse
    {
        $exercises = ReadingExercise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ReadingExercise $exercise): array => $this->formatExercise($exercise));

        return response()->json([
            'success' => true,
            'message' => 'Reading exercises retrieved.',
            'data' => [
                'exercises' => $exercises,
            ],
        ]);
    }

    public function show(ReadingExercise $readingExercise): JsonResponse
    {
        abort_unless($readingExercise->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => 'Reading exercise retrieved.',
            'data' => [
                'exercise' => $this->formatExercise($readingExercise),
            ],
        ]);
    }

    public function evaluate(
        ReadingExercise $readingExercise,
        EvaluateReadingExerciseRequest $request,
    ): JsonResponse {
        abort_unless($readingExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $result = $this->readingEvaluator->evaluate(
            expectedText: $readingExercise->text,
            transcript: $request->validated('transcript'),
        );

        $attempt = ReadingExerciseAttempt::query()->create([
            'user_id' => $user->id,
            'reading_exercise_id' => $readingExercise->id,
            'transcript' => $result['transcript'],
            'score' => $result['score'],
            'status' => $result['status'],
            'is_correct' => $result['is_correct'],
            'words' => $result['words'],
            'feedback' => $result['feedback'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reading evaluated.',
            'data' => [
                'exercise' => $this->formatExercise($readingExercise),
                'result' => $result,
                'attempt' => $this->formatAttemptSummary($attempt),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExercise(ReadingExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'title' => $exercise->title,
            'text' => $exercise->text,
            'language' => $exercise->language,
            'level' => $exercise->level,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttemptSummary(ReadingExerciseAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'is_correct' => $attempt->is_correct,
            'created_at' => $attempt->created_at,
        ];
    }
}
