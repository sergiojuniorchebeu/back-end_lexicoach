<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMode;
use App\Models\ReadingExercise;
use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;
use App\Services\Ai\AiFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminExerciseGenerationController extends Controller
{
    public function __construct(
        private readonly AiFeedbackService $aiFeedbackService,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['reading', 'writing', 'smart-abstract'])],
            'level' => ['required', 'string', 'max:50'],
            'language' => ['required', 'string', 'max:20'],
            'count' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $drafts = $this->aiFeedbackService->generateExercises($validated['type'], $validated);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable.',
                'errors' => [
                    'ai' => [$exception->getMessage()],
                ],
            ], 503);
        }

        $exercises = match ($validated['type']) {
            'reading' => $this->createReadingExercises($drafts),
            'writing' => $this->createWritingExercises($drafts),
            'smart-abstract' => $this->createSmartAbstractExercises($drafts),
        };

        return response()->json([
            'success' => true,
            'message' => count($exercises).' exercise(s) generated.',
            'data' => [
                'exercises' => $exercises,
            ],
        ], 201);
    }

    /**
     * @param  array<int, array<string, mixed>>  $drafts
     * @return array<int, array<string, mixed>>
     */
    private function createReadingExercises(array $drafts): array
    {
        $learningModeId = $this->learningModeId(LearningMode::SLUG_READING);
        $sortOrder = (int) ReadingExercise::query()->max('sort_order');

        return collect($drafts)
            ->map(function (array $draft) use ($learningModeId, &$sortOrder): array {
                $exercise = ReadingExercise::query()->create([
                    'learning_mode_id' => $learningModeId,
                    'title' => $draft['title'],
                    'text' => $draft['text'],
                    'language' => $draft['language'],
                    'level' => $draft['level'],
                    'sort_order' => ++$sortOrder,
                    'is_active' => true,
                ]);

                return [
                    'id' => $exercise->id,
                    'title' => $exercise->title,
                    'level' => $exercise->level,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $drafts
     * @return array<int, array<string, mixed>>
     */
    private function createWritingExercises(array $drafts): array
    {
        $learningModeId = $this->learningModeId(LearningMode::SLUG_WRITING);
        $sortOrder = (int) WritingExercise::query()->max('sort_order');

        return collect($drafts)
            ->map(function (array $draft) use ($learningModeId, &$sortOrder): array {
                $exercise = WritingExercise::query()->create([
                    'learning_mode_id' => $learningModeId,
                    'title' => $draft['title'],
                    'prompt' => $draft['prompt'],
                    'instructions' => $draft['instructions'],
                    'language' => $draft['language'],
                    'level' => $draft['level'],
                    'min_words' => $draft['min_words'],
                    'sort_order' => ++$sortOrder,
                    'is_active' => true,
                ]);

                return [
                    'id' => $exercise->id,
                    'title' => $exercise->title,
                    'level' => $exercise->level,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $drafts
     * @return array<int, array<string, mixed>>
     */
    private function createSmartAbstractExercises(array $drafts): array
    {
        $learningModeId = $this->learningModeId(LearningMode::SLUG_SMART_ABSTRACT);
        $sortOrder = (int) SmartAbstractExercise::query()->max('sort_order');

        return collect($drafts)
            ->map(function (array $draft) use ($learningModeId, &$sortOrder): array {
                $exercise = SmartAbstractExercise::query()->create([
                    'learning_mode_id' => $learningModeId,
                    'title' => $draft['title'],
                    'source_text' => $draft['source_text'],
                    'instructions' => $draft['instructions'],
                    'language' => $draft['language'],
                    'level' => $draft['level'],
                    'min_words' => $draft['min_words'],
                    'max_words' => $draft['max_words'],
                    'sort_order' => ++$sortOrder,
                    'is_active' => true,
                ]);

                return [
                    'id' => $exercise->id,
                    'title' => $exercise->title,
                    'level' => $exercise->level,
                ];
            })
            ->all();
    }

    private function learningModeId(string $slug): ?int
    {
        return LearningMode::query()->where('slug', $slug)->value('id');
    }
}
