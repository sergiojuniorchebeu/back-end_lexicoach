<?php

namespace App\Services;

use App\Models\AiConversationSession;
use App\Models\LearningMode;
use App\Models\User;

class LearningModeProgressSummary
{
    public function __construct(
        private readonly ReadingProgressSummary $readingProgressSummary,
        private readonly WritingProgressSummary $writingProgressSummary,
        private readonly SmartAbstractProgressSummary $smartAbstractProgressSummary,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        $progress = [];

        foreach ($this->activeModes() as $mode) {
            $progress[$mode->slug] = [
                'learning_mode' => $this->formatMode($mode),
                'summary' => match ($mode->slug) {
                    LearningMode::SLUG_READING => $this->readingProgressSummary->forUser($user),
                    LearningMode::SLUG_WRITING => $this->writingProgressSummary->forUser($user),
                    LearningMode::SLUG_SMART_ABSTRACT => $this->smartAbstractProgressSummary->forUser($user),
                    default => $this->readingProgressSummary->empty(),
                },
            ];
        }

        $progress['ai-conversation'] = [
            'learning_mode' => [
                'id' => null,
                'name' => 'AI Conversation',
                'slug' => 'ai-conversation',
            ],
            'summary' => $this->conversationSummaryFor($user),
        ];

        return $progress;
    }

    /**
     * @return array<int, LearningMode>
     */
    public function activeModes(): array
    {
        return LearningMode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatMode(LearningMode $mode): array
    {
        return [
            'id' => $mode->id,
            'name' => $mode->name,
            'slug' => $mode->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationSummaryFor(User $user): array
    {
        $query = AiConversationSession::query()->whereBelongsTo($user);
        $assessedQuery = (clone $query)->whereNotNull('assessment_score');
        $latest = (clone $assessedQuery)->latest('assessed_at')->first();

        return [
            'total_sessions' => (clone $query)->count(),
            'assessed_sessions' => (clone $assessedQuery)->count(),
            'average_score' => (int) round((float) ((clone $assessedQuery)->avg('assessment_score') ?? 0)),
            'best_score' => (int) ((clone $assessedQuery)->max('assessment_score') ?? 0),
            'latest_assessment' => $latest instanceof AiConversationSession ? [
                'id' => $latest->id,
                'score' => $latest->assessment_score,
                'status' => $latest->assessment_status,
                'feedback' => $latest->assessment_feedback,
                'assessed_at' => $latest->assessed_at,
            ] : null,
        ];
    }
}
