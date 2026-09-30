<?php

namespace App\Services;

use Illuminate\Support\Str;

class WordSyllableSplitter
{
    /**
     * @return array<string, mixed>
     */
    public function splitText(string $text, string $language = 'en-US'): array
    {
        $words = collect(preg_split('/\s+/', trim($text)) ?: [])
            ->map(fn (string $word): string => trim($word))
            ->filter()
            ->take(30)
            ->map(fn (string $word): array => $this->splitWord($word))
            ->values()
            ->all();

        return [
            'text' => $text,
            'language' => $language,
            'words' => $words,
            'tips' => [
                'Read one syllable at a time.',
                'Clap once for each syllable.',
                'Join the syllables slowly, then say the full word.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function splitWord(string $rawWord): array
    {
        $word = preg_replace('/[^\p{L}\p{M}\']+/u', '', $rawWord) ?: $rawWord;
        $syllables = $this->syllablesFor($word);

        return [
            'word' => $rawWord,
            'clean_word' => $word,
            'syllables' => $syllables,
            'syllable_count' => count($syllables),
            'display' => implode(' - ', $syllables),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function syllablesFor(string $word): array
    {
        $word = trim($word);

        if (mb_strlen($word) <= 4) {
            return [$word];
        }

        $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $syllables = [];
        $current = '';
        $count = count($letters);

        for ($index = 0; $index < $count; $index++) {
            $current .= $letters[$index];

            if (! $this->isVowel($letters[$index])) {
                continue;
            }

            $next = $letters[$index + 1] ?? null;
            $afterNext = $letters[$index + 2] ?? null;

            if ($next === null) {
                continue;
            }

            if ($this->isVowel($next)) {
                continue;
            }

            if ($afterNext !== null && ! $this->isVowel($afterNext)) {
                $current .= $next;
                $index++;
            }

            if (mb_strlen($current) >= 2 && $index < $count - 1) {
                $syllables[] = $current;
                $current = '';
            }
        }

        if ($current !== '') {
            $syllables[] = $current;
        }

        $syllables = array_values(array_filter($syllables));

        return $syllables === [] ? [$word] : $this->mergeTinyTail($syllables);
    }

    private function isVowel(string $letter): bool
    {
        return Str::contains('aeiouyAEIOUYàâäéèêëïîôöùûüÿÀÂÄÉÈÊËÏÎÔÖÙÛÜŸ', $letter);
    }

    /**
     * @param  array<int, string>  $syllables
     * @return array<int, string>
     */
    private function mergeTinyTail(array $syllables): array
    {
        if (count($syllables) < 2) {
            return $syllables;
        }

        $lastIndex = count($syllables) - 1;

        if (mb_strlen($syllables[$lastIndex]) === 1) {
            $syllables[$lastIndex - 1] .= $syllables[$lastIndex];
            unset($syllables[$lastIndex]);
        }

        return array_values($syllables);
    }
}
