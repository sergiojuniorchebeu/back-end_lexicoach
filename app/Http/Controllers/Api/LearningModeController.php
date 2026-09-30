<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMode;
use App\Models\ReadingExercise;
use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;
use Illuminate\Http\JsonResponse;

class LearningModeController extends Controller
{
    public function index(): JsonResponse
    {
        $modes = LearningMode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (LearningMode $mode): array => $this->formatMode($mode));

        return response()->json([
            'success' => true,
            'message' => 'Learning modes retrieved.',
            'data' => [
                'learning_modes' => $modes,
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $mode = $this->activeModeFromSlug($slug);

        return response()->json([
            'success' => true,
            'message' => 'Learning mode retrieved.',
            'data' => [
                'learning_mode' => $this->formatMode($mode),
            ],
        ]);
    }

    public function exercises(string $slug): JsonResponse
    {
        $mode = $this->activeModeFromSlug($slug);

        $exercises = match ($mode->slug) {
            LearningMode::SLUG_READING => $this->readingExercisesFor($mode),
            LearningMode::SLUG_WRITING => $this->writingExercisesFor($mode),
            LearningMode::SLUG_SMART_ABSTRACT => $this->smartAbstractExercisesFor($mode),
            default => [],
        };

        return response()->json([
            'success' => true,
            'message' => 'Learning mode exercises retrieved.',
            'data' => [
                'learning_mode' => $this->formatMode($mode),
                'exercises' => $exercises,
            ],
        ]);
    }

    private function activeModeFromSlug(string $slug): LearningMode
    {
        return LearningMode::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMode(LearningMode $mode): array
    {
        return [
            'id' => $mode->id,
            'name' => $mode->name,
            'slug' => $mode->slug,
            'description' => $mode->description,
            'is_active' => $mode->is_active,
            'sort_order' => $mode->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReadingExercise(ReadingExercise $exercise): array
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
     * @return array<int, array<string, mixed>>
     */
    private function readingExercisesFor(LearningMode $mode): array
    {
        return ReadingExercise::query()
            ->whereBelongsTo($mode, 'learningMode')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ReadingExercise $exercise): array => $this->formatReadingExercise($exercise))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function writingExercisesFor(LearningMode $mode): array
    {
        return WritingExercise::query()
            ->whereBelongsTo($mode, 'learningMode')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (WritingExercise $exercise): array => [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'prompt' => $exercise->prompt,
                'instructions' => $exercise->instructions,
                'language' => $exercise->language,
                'level' => $exercise->level,
                'min_words' => $exercise->min_words,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function smartAbstractExercisesFor(LearningMode $mode): array
    {
        return SmartAbstractExercise::query()
            ->whereBelongsTo($mode, 'learningMode')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SmartAbstractExercise $exercise): array => [
                'id' => $exercise->id,
                'title' => $exercise->title,
                'source_text' => $exercise->source_text,
                'instructions' => $exercise->instructions,
                'language' => $exercise->language,
                'level' => $exercise->level,
                'min_words' => $exercise->min_words,
                'max_words' => $exercise->max_words,
            ])
            ->all();
    }
}
