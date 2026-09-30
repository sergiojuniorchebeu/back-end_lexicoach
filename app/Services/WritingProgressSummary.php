<?php

namespace App\Services;

use App\Models\User;
use App\Models\WritingExerciseAttempt;

class WritingProgressSummary
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $attemptsQuery = WritingExerciseAttempt::query()
            ->whereBelongsTo($user);

        $totalAttempts = (clone $attemptsQuery)->count();
        $averageScore = $totalAttempts > 0 ? (int) round((float) (clone $attemptsQuery)->avg('score')) : 0;
        $bestScore = $totalAttempts > 0 ? (int) (clone $attemptsQuery)->max('score') : 0;
        $completedExercises = (clone $attemptsQuery)
            ->whereIn('status', ['excellent', 'good'])
            ->distinct('writing_exercise_id')
            ->count('writing_exercise_id');
        $latestAttempt = (clone $attemptsQuery)
            ->with('writingExercise')
            ->latest()
            ->first();

        return [
            'total_attempts' => $totalAttempts,
            'completed_exercises' => $completedExercises,
            'average_score' => $averageScore,
            'best_score' => $bestScore,
            'latest_attempt' => $latestAttempt instanceof WritingExerciseAttempt
                ? $this->formatAttempt($latestAttempt)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function empty(): array
    {
        return [
            'total_attempts' => 0,
            'completed_exercises' => 0,
            'average_score' => 0,
            'best_score' => 0,
            'latest_attempt' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAttempt(WritingExerciseAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'exercise' => [
                'id' => $attempt->writingExercise->id,
                'title' => $attempt->writingExercise->title,
                'prompt' => $attempt->writingExercise->prompt,
                'language' => $attempt->writingExercise->language,
                'level' => $attempt->writingExercise->level,
            ],
            'answer' => $attempt->answer,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'corrected_text' => $attempt->corrected_text,
            'mistakes' => $attempt->mistakes,
            'suggestions' => $attempt->suggestions,
            'feedback' => $attempt->feedback,
            'created_at' => $attempt->created_at,
        ];
    }
}
