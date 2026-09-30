<?php

namespace App\Services;

use App\Models\SmartAbstractAttempt;
use App\Models\User;

class SmartAbstractProgressSummary
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $attemptsQuery = SmartAbstractAttempt::query()
            ->whereBelongsTo($user);

        $totalAttempts = (clone $attemptsQuery)->count();
        $averageScore = $totalAttempts > 0 ? (int) round((float) (clone $attemptsQuery)->avg('score')) : 0;
        $bestScore = $totalAttempts > 0 ? (int) (clone $attemptsQuery)->max('score') : 0;
        $completedExercises = (clone $attemptsQuery)
            ->whereIn('status', ['excellent', 'good'])
            ->distinct('smart_abstract_exercise_id')
            ->count('smart_abstract_exercise_id');
        $latestAttempt = (clone $attemptsQuery)
            ->with('smartAbstractExercise')
            ->latest()
            ->first();

        return [
            'total_attempts' => $totalAttempts,
            'completed_exercises' => $completedExercises,
            'average_score' => $averageScore,
            'best_score' => $bestScore,
            'latest_attempt' => $latestAttempt instanceof SmartAbstractAttempt
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
    public function formatAttempt(SmartAbstractAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'exercise' => [
                'id' => $attempt->smartAbstractExercise->id,
                'title' => $attempt->smartAbstractExercise->title,
                'source_text' => $attempt->smartAbstractExercise->source_text,
                'language' => $attempt->smartAbstractExercise->language,
                'level' => $attempt->smartAbstractExercise->level,
            ],
            'summary' => $attempt->summary,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'improved_summary' => $attempt->improved_summary,
            'missing_ideas' => $attempt->missing_ideas,
            'strengths' => $attempt->strengths,
            'feedback' => $attempt->feedback,
            'created_at' => $attempt->created_at,
        ];
    }
}
