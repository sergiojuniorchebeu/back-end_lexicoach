<?php

namespace App\Services;

use App\Models\SmartAbstractAttempt;
use App\Models\User;

class SmartAbstractProgressSummary
{
    /**
     * Smart Abstract is never graded: the AI only summarizes the document,
     * so progress here is tracked by volume (how many summaries were
     * generated), not by a score.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $attemptsQuery = SmartAbstractAttempt::query()
            ->whereBelongsTo($user);

        $totalSummaries = (clone $attemptsQuery)->count();
        $documentsCompleted = (clone $attemptsQuery)
            ->distinct('smart_abstract_exercise_id')
            ->count('smart_abstract_exercise_id');
        $latestAttempt = (clone $attemptsQuery)
            ->with('smartAbstractExercise')
            ->latest()
            ->first();

        return [
            'total_summaries' => $totalSummaries,
            'documents_completed' => $documentsCompleted,
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
            'total_summaries' => 0,
            'documents_completed' => 0,
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
            'key_points' => $attempt->strengths,
            'created_at' => $attempt->created_at,
        ];
    }
}
