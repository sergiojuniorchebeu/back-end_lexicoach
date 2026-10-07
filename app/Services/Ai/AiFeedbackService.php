<?php

namespace App\Services\Ai;

use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;
use App\Services\Ai\Conversation\ConversationControlService;
use InvalidArgumentException;

class AiFeedbackService
{
    public function __construct(
        private readonly FakeAiProvider $fakeAiProvider,
        private readonly GeminiAiProvider $geminiAiProvider,
        private readonly ConversationControlService $conversationControlService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function evaluateWriting(WritingExercise $exercise, string $answer): array
    {
        return $this->normalizeWriting($this->provider()->evaluateWriting($exercise, $answer), $answer);
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateSmartAbstract(SmartAbstractExercise $exercise, string $documentText): array
    {
        return $this->normalizeSmartAbstract(
            $this->provider()->evaluateSmartAbstract($exercise, $documentText),
            $documentText,
        );
    }

    /**
     * @param  array{level: string, language: string, count: int}  $params
     * @return array<int, array<string, mixed>>
     */
    public function generateExercises(string $type, array $params): array
    {
        $exercises = $this->provider()->generateExercises($type, $params);

        return collect($exercises)
            ->map(fn (mixed $exercise): array => $this->normalizeGeneratedExercise(
                is_array($exercise) ? $exercise : [],
                $type,
                $params,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $exercise
     * @param  array{level: string, language: string, count: int}  $params
     * @return array<string, mixed>
     */
    private function normalizeGeneratedExercise(array $exercise, string $type, array $params): array
    {
        $title = $this->stringFrom($exercise['title'] ?? '', 'Untitled exercise');
        $level = $this->stringFrom($exercise['level'] ?? $params['level'], $params['level']);

        return match ($type) {
            'reading' => [
                'title' => $title,
                'text' => $this->stringFrom($exercise['text'] ?? '', 'Read this sentence aloud.'),
                'language' => $params['language'],
                'level' => $level,
            ],
            'writing' => [
                'title' => $title,
                'prompt' => $this->stringFrom($exercise['prompt'] ?? '', 'Write a few sentences.'),
                'instructions' => $this->stringFrom($exercise['instructions'] ?? '', ''),
                'min_words' => max(1, (int) ($exercise['min_words'] ?? 8)),
                'language' => $params['language'],
                'level' => $level,
            ],
            'smart-abstract' => [
                'title' => $title,
                'source_text' => $this->stringFrom($exercise['source_text'] ?? '', 'Document to summarize.'),
                'instructions' => $this->stringFrom($exercise['instructions'] ?? '', ''),
                'min_words' => max(1, (int) ($exercise['min_words'] ?? 15)),
                'max_words' => max(
                    (int) ($exercise['min_words'] ?? 15) + 1,
                    (int) ($exercise['max_words'] ?? 40),
                ),
                'language' => $params['language'],
                'level' => $level,
            ],
            default => throw new InvalidArgumentException("Unsupported exercise type: {$type}"),
        };
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function evaluateConversation(array $messages): array
    {
        return $this->normalizeConversation($this->provider()->evaluateConversation($messages));
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function replyToConversation(array $messages): array
    {
        $control = $this->conversationControlService->buildControl($messages);
        $rawReply = $this->provider()->replyToConversation($messages, $control);

        return $this->normalizeConversationReply(
            $this->conversationControlService->cleanOrFallback($rawReply, $control),
        );
    }

    private function provider(): AiProvider
    {
        return match (config('ai.provider')) {
            'fake' => $this->fakeAiProvider,
            'gemini' => $this->geminiAiProvider,
            default => throw new InvalidArgumentException('Unsupported AI provider.'),
        };
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeWriting(array $result, string $answer): array
    {
        $score = $this->scoreFrom($result['score'] ?? 0);

        return [
            'score' => $score,
            'status' => $this->statusFrom($result['status'] ?? null, $score),
            'answer' => $answer,
            'corrected_text' => $this->stringFrom($result['corrected_text'] ?? $answer, $answer),
            'mistakes' => $this->arrayFrom($result['mistakes'] ?? []),
            'suggestions' => $this->arrayFrom($result['suggestions'] ?? []),
            'feedback' => $this->feedbackFrom($result['feedback'] ?? null, $score),
            'raw_ai_response' => $result['raw_ai_response'] ?? $result,
        ];
    }

    /**
     * Normalizes a pure summary of the document. This never grades the
     * learner - there is no score and no evaluation, only the AI's summary
     * of the text and its key points. The `score`/`status` fields are kept
     * as fixed, non-evaluative values purely for storage/dashboard
     * compatibility with the other (actually graded) exercise types.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeSmartAbstract(array $result, string $documentText): array
    {
        $summary = $this->stringFrom(
            $result['summary'] ?? $result['improved_summary'] ?? '',
            'This document has been summarized in simple words.',
        );
        $keyPoints = $this->arrayFrom($result['key_points'] ?? []);

        return [
            'score' => 100,
            'status' => 'good',
            'document_text' => $documentText,
            'summary' => $summary,
            'improved_summary' => $summary,
            'missing_ideas' => [],
            'strengths' => $keyPoints,
            'feedback' => [
                'title' => 'Summary ready',
                'message' => $keyPoints !== []
                    ? 'Here is your summary, with the key points from your document.'
                    : 'Here is your summary.',
            ],
            'raw_ai_response' => $result['raw_ai_response'] ?? $result,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeConversation(array $result): array
    {
        $score = $this->scoreFrom($result['score'] ?? 0);

        return [
            'score' => $score,
            'status' => $this->statusFrom($result['status'] ?? null, $score),
            'fluency' => $this->scoreFrom($result['fluency'] ?? $score),
            'vocabulary' => $this->scoreFrom($result['vocabulary'] ?? $score),
            'grammar' => $this->scoreFrom($result['grammar'] ?? $score),
            'confidence' => $this->scoreFrom($result['confidence'] ?? $score),
            'strengths' => $this->arrayFrom($result['strengths'] ?? []),
            'mistakes' => $this->arrayFrom($result['mistakes'] ?? []),
            'dyslexia_support' => $this->arrayFrom($result['dyslexia_support'] ?? []),
            'recommended_next_step' => $this->stringFrom(
                $result['recommended_next_step'] ?? '',
                'Practice one short answer again.',
            ),
            'feedback' => $this->feedbackFrom($result['feedback'] ?? null, $score),
            'raw_ai_response' => $result['raw_ai_response'] ?? $result,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeConversationReply(array $result): array
    {
        $reply = $this->stringFrom(
            $result['reply'] ?? '',
            'Good. Can you tell me one more thing?',
        );

        return [
            'reply' => $reply,
            'language' => $this->stringFrom($result['language'] ?? '', 'en-US'),
            'intent' => $this->stringFrom($result['intent'] ?? '', 'generalConversation'),
            'task' => $this->stringFrom($result['task'] ?? '', ''),
            'fallback_used' => (bool) ($result['fallback_used'] ?? false),
            'fallback_reason' => $result['fallback_reason'] ?? null,
            'gentle_correction' => $this->stringFrom($result['gentle_correction'] ?? '', ''),
            'encouragement' => $this->stringFrom($result['encouragement'] ?? '', 'Take your time.'),
            'next_question' => $this->stringFrom($result['next_question'] ?? '', 'What else can you say?'),
            'raw_ai_response' => $result['raw_ai_response'] ?? $result,
        ];
    }

    private function scoreFrom(mixed $score): int
    {
        if (! is_numeric($score)) {
            return 0;
        }

        return max(0, min(100, (int) round((float) $score)));
    }

    private function statusFrom(mixed $status, int $score): string
    {
        if (is_string($status) && in_array($status, ['excellent', 'good', 'needs_practice'], true)) {
            return $status;
        }

        if ($score >= 90) {
            return 'excellent';
        }

        if ($score >= 75) {
            return 'good';
        }

        return 'needs_practice';
    }

    private function stringFrom(mixed $value, string $fallback): string
    {
        return is_string($value) && trim($value) !== '' ? $value : $fallback;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function arrayFrom(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, string>
     */
    private function feedbackFrom(mixed $feedback, int $score): array
    {
        if (
            is_array($feedback)
            && isset($feedback['title'], $feedback['message'])
            && is_string($feedback['title'])
            && is_string($feedback['message'])
        ) {
            return [
                'title' => $feedback['title'],
                'message' => $feedback['message'],
            ];
        }

        return [
            'title' => $score >= 75 ? 'Good work!' : 'Keep practicing.',
            'message' => $score >= 75
                ? 'Your answer is understandable. Improve details and punctuation.'
                : 'Try again with more detail and clearer sentences.',
        ];
    }
}
