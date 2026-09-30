<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateSmartAbstractExerciseRequest;
use App\Models\SmartAbstractAttempt;
use App\Models\SmartAbstractExercise;
use App\Models\User;
use App\Services\Ai\AiFeedbackService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SmartAbstractExerciseController extends Controller
{
    public function __construct(
        private readonly AiFeedbackService $aiFeedbackService,
    ) {}

    public function index(): JsonResponse
    {
        $exercises = SmartAbstractExercise::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SmartAbstractExercise $exercise): array => $this->formatExercise($exercise));

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract exercises retrieved.',
            'data' => [
                'exercises' => $exercises,
            ],
        ]);
    }

    public function show(SmartAbstractExercise $smartAbstractExercise): JsonResponse
    {
        abort_unless($smartAbstractExercise->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract exercise retrieved.',
            'data' => [
                'exercise' => $this->formatExercise($smartAbstractExercise),
            ],
        ]);
    }

    public function evaluate(
        SmartAbstractExercise $smartAbstractExercise,
        EvaluateSmartAbstractExerciseRequest $request,
    ): JsonResponse {
        abort_unless($smartAbstractExercise->is_active, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $result = $this->aiFeedbackService->evaluateSmartAbstract(
                exercise: $smartAbstractExercise,
                documentText: $request->validated('document_text'),
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

        $attempt = SmartAbstractAttempt::query()->create([
            'user_id' => $user->id,
            'smart_abstract_exercise_id' => $smartAbstractExercise->id,
            'summary' => $result['summary'],
            'score' => $result['score'],
            'status' => $result['status'],
            'improved_summary' => $result['improved_summary'],
            'missing_ideas' => $result['missing_ideas'],
            'strengths' => $result['strengths'],
            'feedback' => $result['feedback'],
            'raw_ai_response' => [
                'document_text' => $result['document_text'],
                'ai' => $result['raw_ai_response'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Smart abstract evaluated.',
            'data' => [
                'exercise' => $this->formatExercise($smartAbstractExercise),
                'result' => $result,
                'attempt' => $this->formatAttemptSummary($attempt),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExercise(SmartAbstractExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'title' => $exercise->title,
            'source_text' => $exercise->source_text,
            'instructions' => $exercise->instructions,
            'language' => $exercise->language,
            'level' => $exercise->level,
            'min_words' => $exercise->min_words,
            'max_words' => $exercise->max_words,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttemptSummary(SmartAbstractAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'created_at' => $attempt->created_at,
        ];
    }
}
