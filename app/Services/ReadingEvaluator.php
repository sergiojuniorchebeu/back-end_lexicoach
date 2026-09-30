<?php

namespace App\Services;

class ReadingEvaluator
{
    /**
     * @return array<string, mixed>
     */
    public function evaluate(string $expectedText, string $transcript): array
    {
        $expectedWords = $this->wordsFrom($expectedText);
        $actualWords = $this->wordsFrom($transcript);

        $words = $this->compareWords($expectedWords, $actualWords);

        $correctCount = $this->countStatus($words, 'correct');
        $errorCount = count($words) - $correctCount;
        $expectedCount = max(count($expectedWords), 1);
        $score = max(0, (int) round((1 - ($errorCount / $expectedCount)) * 100));
        $status = $this->statusFromScore($score);

        return [
            'score' => $score,
            'status' => $status,
            'is_correct' => $score === 100,
            'transcript' => $transcript,
            'words' => $words,
            'feedback' => $this->feedback($score, $words),
        ];
    }

    /**
     * @return array<int, array{text: string, normalized: string}>
     */
    private function wordsFrom(string $text): array
    {
        $normalizedText = mb_strtolower($text);
        $normalizedText = preg_replace("/[^\p{L}\p{N}']+/u", ' ', $normalizedText) ?? '';
        $normalizedText = trim($normalizedText);

        if ($normalizedText === '') {
            return [];
        }

        $normalizedWords = preg_split('/\s+/', $normalizedText) ?: [];
        $originalWords = preg_split('/\s+/', trim($text)) ?: [];

        return array_map(
            fn (string $word, int $index): array => [
                'text' => $this->cleanDisplayWord($originalWords[$index] ?? $word),
                'normalized' => $word,
            ],
            $normalizedWords,
            array_keys($normalizedWords),
        );
    }

    /**
     * @param  array<int, array{text: string, normalized: string}>  $expectedWords
     * @param  array<int, array{text: string, normalized: string}>  $actualWords
     * @return array<int, array<string, string|null>>
     */
    private function compareWords(array $expectedWords, array $actualWords): array
    {
        $expectedCount = count($expectedWords);
        $actualCount = count($actualWords);
        $distance = array_fill(0, $expectedCount + 1, array_fill(0, $actualCount + 1, 0));

        for ($i = 0; $i <= $expectedCount; $i++) {
            $distance[$i][0] = $i;
        }

        for ($j = 0; $j <= $actualCount; $j++) {
            $distance[0][$j] = $j;
        }

        for ($i = 1; $i <= $expectedCount; $i++) {
            for ($j = 1; $j <= $actualCount; $j++) {
                $cost = $expectedWords[$i - 1]['normalized'] === $actualWords[$j - 1]['normalized'] ? 0 : 1;

                $distance[$i][$j] = min(
                    $distance[$i - 1][$j] + 1,
                    $distance[$i][$j - 1] + 1,
                    $distance[$i - 1][$j - 1] + $cost,
                );
            }
        }

        $words = [];
        $i = $expectedCount;
        $j = $actualCount;

        while ($i > 0 || $j > 0) {
            if (
                $i > 0
                && $j > 0
                && $expectedWords[$i - 1]['normalized'] === $actualWords[$j - 1]['normalized']
                && $distance[$i][$j] === $distance[$i - 1][$j - 1]
            ) {
                $words[] = [
                    'expected' => $expectedWords[$i - 1]['text'],
                    'actual' => $actualWords[$j - 1]['text'],
                    'status' => 'correct',
                ];
                $i--;
                $j--;

                continue;
            }

            if ($i > 0 && $j > 0 && $distance[$i][$j] === $distance[$i - 1][$j - 1] + 1) {
                $words[] = [
                    'expected' => $expectedWords[$i - 1]['text'],
                    'actual' => $actualWords[$j - 1]['text'],
                    'status' => 'incorrect',
                ];
                $i--;
                $j--;

                continue;
            }

            if ($i > 0 && $distance[$i][$j] === $distance[$i - 1][$j] + 1) {
                $words[] = [
                    'expected' => $expectedWords[$i - 1]['text'],
                    'actual' => null,
                    'status' => 'missing',
                ];
                $i--;

                continue;
            }

            $words[] = [
                'expected' => null,
                'actual' => $actualWords[$j - 1]['text'],
                'status' => 'extra',
            ];
            $j--;
        }

        return array_reverse($words);
    }

    private function cleanDisplayWord(string $word): string
    {
        return trim($word, " \t\n\r\0\x0B.,!?;:\"()[]{}");
    }

    /**
     * @param  array<int, array<string, string|null>>  $words
     */
    private function countStatus(array $words, string $status): int
    {
        return count(array_filter($words, fn (array $word): bool => $word['status'] === $status));
    }

    private function statusFromScore(int $score): string
    {
        if ($score >= 95) {
            return 'excellent';
        }

        if ($score >= 75) {
            return 'good';
        }

        return 'needs_practice';
    }

    /**
     * @param  array<int, array<string, string|null>>  $words
     * @return array<string, string>
     */
    private function feedback(int $score, array $words): array
    {
        if ($score === 100) {
            return [
                'title' => 'Excellent!',
                'message' => 'You read the sentence correctly.',
            ];
        }

        $missingWords = array_values(array_filter($words, fn (array $word): bool => $word['status'] === 'missing'));
        $incorrectWords = array_values(array_filter($words, fn (array $word): bool => $word['status'] === 'incorrect'));

        if ($score >= 75) {
            return [
                'title' => 'Good job!',
                'message' => 'You read most of the sentence correctly. Try again and focus on the highlighted words.',
            ];
        }

        if (count($missingWords) > count($incorrectWords)) {
            return [
                'title' => 'Keep practicing.',
                'message' => 'Some words were missing. Read slowly and try to say every word.',
            ];
        }

        return [
            'title' => 'Try again.',
            'message' => 'Some words were different from the exercise text. Listen once, then repeat the sentence.',
        ];
    }
}
