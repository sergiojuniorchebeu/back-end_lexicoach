<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateWritingExerciseRequest;
use App\Models\User;
use App\Models\WritingExercise;
use App\Models\WritingExerciseAttempt;
use App\Services\Ai\AiFeedbackService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class WritingExerciseController extends Controller
{
    public function __construct(
        private readonly AiFeedbackService $aiFeedbackService,
    ) {}

    public function index(): JsonResponse
    {
        $exercises = WritingExercise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (WritingExercise $exercise): array => $this->formatExercise($exercise));

        return response()->json([
            'success' => true,
            'message' => 'Writing exercises retrieved.',
            'data' => [
                'exercises' => $exercises,
            ],
        ]);
    }

    public function show(WritingExercise $writingExercise): JsonResponse
    {
        abort_unless($writingExercise->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => 'Writing exercise retrieved.',
            'data' => [
                'exercise' => $this->formatExercise($writingExercise),
            ],
        ]);
    }

    public function evaluate(
        WritingExercise $writingExercise,
        EvaluateWritingExerciseRequest $request,
    ): JsonResponse {
        abort_unless($writingExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $result = $this->aiFeedbackService->evaluateWriting(
                exercise: $writingExercise,
                answer: $request->validated('answer'),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable.',
                'errors' => [
                    'ai' => [$exception->getMessage()],
                ],
            ], 503);
        }

        $attempt = WritingExerciseAttempt::query()->create([
            'user_id' => $user->id,
            'writing_exercise_id' => $writingExercise->id,
            'answer' => $result['answer'],
            'score' => $result['score'],
            'status' => $result['status'],
            'corrected_text' => $result['corrected_text'],
            'mistakes' => $result['mistakes'],
            'suggestions' => $result['suggestions'],
            'feedback' => $result['feedback'],
            'raw_ai_response' => $result['raw_ai_response'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Writing text evaluated.',
            'data' => [
                'exercise' => $this->formatExercise($writingExercise),
                'result' => $result,
                'attempt' => $this->formatAttemptSummary($attempt),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExercise(WritingExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'title' => $exercise->title,
            'prompt' => $exercise->prompt,
            'instructions' => $exercise->instructions,
            'language' => $exercise->language,
            'level' => $exercise->level,
            'min_words' => $exercise->min_words,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttemptSummary(WritingExerciseAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'created_at' => $attempt->created_at,
        ];
    }
}
