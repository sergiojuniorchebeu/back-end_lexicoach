<?php

namespace App\Services;

use App\Models\ReadingExerciseAttempt;
use App\Models\User;

class ReadingProgressSummary
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $attemptsQuery = ReadingExerciseAttempt::query()
            ->whereBelongsTo($user);

        $totalAttempts = (clone $attemptsQuery)->count();
        $averageScore = $totalAttempts > 0 ? (int) round((float) (clone $attemptsQuery)->avg('score')) : 0;
        $bestScore = $totalAttempts > 0 ? (int) (clone $attemptsQuery)->max('score') : 0;
        $completedExercises = (clone $attemptsQuery)
            ->where('is_correct', true)
            ->distinct('reading_exercise_id')
            ->count('reading_exercise_id');
        $latestAttempt = (clone $attemptsQuery)
            ->with('readingExercise')
            ->latest()
            ->first();

        return [
            'total_attempts' => $totalAttempts,
            'completed_exercises' => $completedExercises,
            'average_score' => $averageScore,
            'best_score' => $bestScore,
            'latest_attempt' => $latestAttempt instanceof ReadingExerciseAttempt
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
    public function formatAttempt(ReadingExerciseAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'exercise' => [
                'id' => $attempt->readingExercise->id,
                'title' => $attempt->readingExercise->title,
                'text' => $attempt->readingExercise->text,
                'language' => $attempt->readingExercise->language,
                'level' => $attempt->readingExercise->level,
            ],
            'transcript' => $attempt->transcript,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'is_correct' => $attempt->is_correct,
            'words' => $attempt->words,
            'feedback' => $attempt->feedback,
            'created_at' => $attempt->created_at,
        ];
    }
}
