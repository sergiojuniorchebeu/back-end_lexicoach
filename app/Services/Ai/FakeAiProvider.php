<?php

namespace App\Services\Ai;

use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;
use Illuminate\Support\Str;

class FakeAiProvider implements AiProvider
{
    /**
     * @return array<string, mixed>
     */
    public function evaluateWriting(WritingExercise $exercise, string $answer): array
    {
        $wordCount = str_word_count($answer);
        $score = $wordCount >= $exercise->min_words ? 82 : 58;

        return [
            'score' => $score,
            'status' => $this->statusFromScore($score),
            'corrected_text' => $answer,
            'mistakes' => $wordCount >= $exercise->min_words ? [] : [
                [
                    'type' => 'too_short',
                    'text' => null,
                    'explanation' => 'The answer is too short for this exercise.',
                ],
            ],
            'suggestions' => [
                'Add one clear example.',
                'Check punctuation before submitting.',
            ],
            'feedback' => [
                'title' => $score >= 75 ? 'Good work!' : 'Keep practicing.',
                'message' => $score >= 75
                    ? 'Your answer is understandable. Improve details and punctuation.'
                    : 'Write a longer answer and make one clear sentence for each idea.',
            ],
            'raw_ai_response' => [
                'provider' => 'fake',
                'note' => 'Deterministic local response used when no AI key is configured.',
            ],
        ];
    }

    /**
     * Summarizes the document. This never grades the learner: there is no
     * score, no "mistakes" and no judgement, only a summary of the text.
     *
     * @return array<string, mixed>
     */
    public function evaluateSmartAbstract(SmartAbstractExercise $exercise, string $documentText): array
    {
        $words = preg_split('/\s+/', trim($documentText)) ?: [];
        $summary = collect($words)
            ->take(max($exercise->min_words, min($exercise->max_words, 45)))
            ->implode(' ');
        $summary = Str::finish($summary, '.');

        $sentences = array_values(array_filter(
            array_map('trim', preg_split('/(?<=[.!?])\s+/', trim($documentText)) ?: []),
        ));

        return [
            'summary' => $summary,
            'key_points' => array_slice($sentences, 0, 3),
            'raw_ai_response' => [
                'provider' => 'fake',
                'note' => 'Deterministic local response used when no AI key is configured.',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function evaluateConversation(array $messages): array
    {
        $learnerText = collect($messages)
            ->where('role', 'learner')
            ->pluck('content')
            ->implode(' ');
        $wordCount = str_word_count($learnerText);
        $score = $wordCount >= 12 ? 78 : 58;

        return [
            'score' => $score,
            'status' => $this->statusFromScore($score),
            'fluency' => max(45, min(90, $score - 4)),
            'vocabulary' => max(45, min(90, $score + 2)),
            'grammar' => max(40, min(88, $score - 6)),
            'confidence' => max(50, min(92, $score + 5)),
            'strengths' => [
                'The learner participated in the conversation.',
            ],
            'mistakes' => $score >= 75 ? [] : [
                [
                    'type' => 'too_short',
                    'example' => null,
                    'gentle_correction' => 'Try to answer with one complete short sentence.',
                ],
            ],
            'dyslexia_support' => [
                'Use short prompts.',
                'Repeat one useful sentence slowly.',
                'Focus on one correction at a time.',
            ],
            'recommended_next_step' => $score >= 75
                ? 'Practice the same topic again and add one detail.'
                : 'Practice answering with subject + verb + one detail.',
            'feedback' => [
                'title' => $score >= 75 ? 'Good conversation!' : 'Keep practicing.',
                'message' => $score >= 75
                    ? 'The learner understood the exchange. Continue with short daily conversations.'
                    : 'The learner needs more support with short complete answers.',
            ],
            'raw_ai_response' => [
                'provider' => 'fake',
                'note' => 'Deterministic local response used when no AI key is configured.',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function replyToConversation(array $messages, array $control): array
    {
        $latestLearnerMessage = collect($messages)
            ->where('role', 'learner')
            ->pluck('content')
            ->last() ?: 'Hello';

        return [
            'reply' => "I understood: {$latestLearnerMessage}. Good effort. Can you tell me one more detail?",
            'language' => 'en-US',
            'gentle_correction' => 'Try to answer with one complete short sentence.',
            'encouragement' => 'You are doing well. Take your time.',
            'next_question' => 'Can you say that again with one extra detail?',
            'intent' => $control['intent'] ?? 'generalConversation',
            'task' => $control['task'] ?? null,
            'raw_ai_response' => [
                'provider' => 'fake',
                'note' => 'Deterministic local conversation reply used when no AI key is configured.',
            ],
        ];
    }

    private function statusFromScore(int $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        }

        if ($score >= 75) {
            return 'good';
        }

        return 'needs_practice';
    }
}
