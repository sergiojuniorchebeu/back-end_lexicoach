<?php

namespace App\Services\Ai;

use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class GeminiAiProvider implements AiProvider
{
    public function __construct(
        private readonly GeminiKeyPool $keyPool = new GeminiKeyPool,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function evaluateWriting(WritingExercise $exercise, string $answer): array
    {
        $prompt = <<<PROMPT
You are LexiCoach, an educational writing assistant for a dyslexic learner.
Evaluate the learner answer with kindness and clear language.

Exercise title: {$exercise->title}
Language: {$exercise->language}
Level: {$exercise->level}
Prompt: {$exercise->prompt}
Instructions: {$exercise->instructions}
Minimum words: {$exercise->min_words}

Learner answer:
{$answer}

Return only the JSON object requested by the schema.
PROMPT;

        return $this->generateJson($prompt, $this->writingSchema(), [], 'writing_evaluation');
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateSmartAbstract(SmartAbstractExercise $exercise, string $documentText): array
    {
        $prompt = <<<PROMPT
You are LexiCoach, an educational smart summary assistant for a dyslexic learner.
Your only task is to summarize the document below. You do NOT grade, score or
evaluate the learner in any way - there is no student answer here, only a
document to summarize.
Use clear words and keep the main ideas only.

Exercise title: {$exercise->title}
Language: {$exercise->language}
Level: {$exercise->level}
Instructions: {$exercise->instructions}
Target summary length: {$exercise->min_words} to {$exercise->max_words} words.

Document to summarize:
{$documentText}

Return only the JSON object requested by the schema: a "summary" of the
document and 2 to 4 "key_points" taken from the document itself.
PROMPT;

        return $this->generateJson($prompt, $this->smartAbstractSchema(), [], 'smart_abstract_summary');
    }

    /**
     * @param  array{level: string, language: string, count: int}  $params
     * @return array<int, array<string, mixed>>
     */
    public function generateExercises(string $type, array $params): array
    {
        $level = $params['level'];
        $language = $params['language'];
        $count = $params['count'];

        $guidance = match ($type) {
            'reading' => 'Each exercise is a short sentence or paragraph the learner will read aloud. Keep sentences short and phonetically simple.',
            'writing' => 'Each exercise is a writing prompt the learner answers in a few sentences. Give clear, concrete instructions.',
            'smart-abstract' => 'Each exercise is a short source document the learner will summarize. Write a self-contained paragraph with a clear main idea.',
            default => throw new InvalidArgumentException("Unsupported exercise type: {$type}"),
        };

        $prompt = <<<PROMPT
You are LexiCoach, an educational content generator for dyslexic learners.
Generate exactly {$count} new, distinct exercises.
Language: {$language}
Level: {$level}
{$guidance}

Use simple, clear, dyslexia-friendly wording. Do not repeat the same topic
twice. Return only the JSON object requested by the schema.
PROMPT;

        $result = $this->generateJson($prompt, $this->exerciseGenerationSchema($type), [], "generate_{$type}_exercises");

        $exercises = $result['exercises'] ?? [];

        return is_array($exercises) ? $exercises : [];
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function evaluateConversation(array $messages): array
    {
        $transcript = collect($messages)
            ->map(fn (array $message): string => strtoupper($message['role'] ?? 'unknown').': '.($message['content'] ?? ''))
            ->implode("\n");

        $prompt = <<<PROMPT
You are LexiCoach, a language learning coach for a dyslexic learner.
Evaluate the learner's progress from this short AI conversation.

Rules:
- Be kind and practical.
- Use simple language.
- Do not shame the learner.
- Focus on communication, confidence, short sentence structure, vocabulary, and grammar.
- Give dyslexia-friendly feedback: one correction at a time, short next step, clear strengths.

Transcript:
{$transcript}

Return only the JSON object requested by the schema.
PROMPT;

        return $this->generateJson($prompt, $this->conversationSchema(), [], 'conversation_assessment');
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function replyToConversation(array $messages, array $control): array
    {
        $prompt = (string) ($control['prompt'] ?? '');

        return $this->generateJson($prompt, $this->conversationReplySchema(), [
            'temperature' => 0.2,
            'topP' => 0.7,
            'maxOutputTokens' => 180,
        ], 'conversation_reply');
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function generateJson(
        string $prompt,
        array $schema,
        array $generationOverrides = [],
        string $debugLabel = 'gemini_json',
    ): array
    {
        if (! $this->keyPool->hasAnyKey()) {
            throw new RuntimeException('GEMINI_API_KEY is missing.');
        }

        $baseUrl = rtrim((string) config('ai.gemini.base_url'), '/');
        $model = (string) config('ai.gemini.model');
        $timeout = (int) config('ai.gemini.timeout');
        $generationConfig = [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
            ...$generationOverrides,
        ];

        $this->logGeminiDebug('Gemini request prepared', [
            'label' => $debugLabel,
            'model' => $model,
            'prompt_length' => mb_strlen($prompt),
            'prompt' => $prompt,
            'generation_config' => $generationConfig,
        ]);

        $apiKey = $this->keyPool->currentKey();
        $attemptsLeft = 2; // current key, then one fallback key

        while (true) {
            if ($apiKey === null) {
                throw new RuntimeException('All Gemini keys are exhausted.');
            }

            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post("{$baseUrl}/models/{$model}:generateContent", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => $generationConfig,
                    ]);
            } catch (ConnectionException $exception) {
                $this->logGeminiDebug('Gemini connection failed', [
                    'label' => $debugLabel,
                    'model' => $model,
                    'error' => $exception->getMessage(),
                ]);

                throw new RuntimeException('Gemini is unreachable: '.$exception->getMessage(), previous: $exception);
            }

            $attemptsLeft--;

            if ($this->isQuotaExhausted($response) && $attemptsLeft > 0) {
                $this->logGeminiDebug('Gemini key exhausted, switching to next key', [
                    'label' => $debugLabel,
                    'model' => $model,
                    'status' => $response->status(),
                ]);

                $this->keyPool->markExhausted($apiKey);
                $apiKey = $this->keyPool->nextKeyAfter($apiKey);

                continue;
            }

            break;
        }

        if ($response->failed()) {
            $this->logGeminiDebug('Gemini request failed', [
                'label' => $debugLabel,
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        $this->logGeminiDebug('Gemini raw response received', [
            'label' => $debugLabel,
            'model' => $model,
            'status' => $response->status(),
            'raw_text_length' => is_string($text) ? mb_strlen($text) : 0,
            'raw_text' => is_string($text) ? $text : null,
            'body_preview' => mb_substr($response->body(), 0, 6000),
        ]);

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $json = json_decode($text, true);

        if (! is_array($json)) {
            $this->logGeminiDebug('Gemini invalid JSON', [
                'label' => $debugLabel,
                'model' => $model,
                'raw_text' => $text,
            ]);

            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        $this->logGeminiDebug('Gemini JSON decoded', [
            'label' => $debugLabel,
            'model' => $model,
            'json' => $json,
        ]);

        return $json;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logGeminiDebug(string $message, array $context): void
    {
        if (! config('ai.conversation_debug_logs', true)) {
            return;
        }

        Log::info($message, $context);
    }

    /**
     * Gemini returns 429 (or sometimes 403 with a RESOURCE_EXHAUSTED
     * status) when the key's quota is used up.
     */
    private function isQuotaExhausted(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        if ($response->status() === 403 && str_contains($response->body(), 'RESOURCE_EXHAUSTED')) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function writingSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'status' => ['type' => 'STRING'],
                'corrected_text' => ['type' => 'STRING'],
                'mistakes' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING'],
                            'text' => ['type' => 'STRING'],
                            'explanation' => ['type' => 'STRING'],
                        ],
                    ],
                ],
                'suggestions' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
                'feedback' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'title' => ['type' => 'STRING'],
                        'message' => ['type' => 'STRING'],
                    ],
                ],
            ],
            'required' => ['score', 'status', 'corrected_text', 'mistakes', 'suggestions', 'feedback'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function smartAbstractSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'summary' => ['type' => 'STRING'],
                'key_points' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['summary', 'key_points'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exerciseGenerationSchema(string $type): array
    {
        $itemProperties = match ($type) {
            'reading' => [
                'title' => ['type' => 'STRING'],
                'text' => ['type' => 'STRING'],
                'level' => ['type' => 'STRING'],
            ],
            'writing' => [
                'title' => ['type' => 'STRING'],
                'prompt' => ['type' => 'STRING'],
                'instructions' => ['type' => 'STRING'],
                'min_words' => ['type' => 'INTEGER'],
                'level' => ['type' => 'STRING'],
            ],
            'smart-abstract' => [
                'title' => ['type' => 'STRING'],
                'source_text' => ['type' => 'STRING'],
                'instructions' => ['type' => 'STRING'],
                'min_words' => ['type' => 'INTEGER'],
                'max_words' => ['type' => 'INTEGER'],
                'level' => ['type' => 'STRING'],
            ],
            default => throw new InvalidArgumentException("Unsupported exercise type: {$type}"),
        };

        return [
            'type' => 'OBJECT',
            'properties' => [
                'exercises' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => $itemProperties,
                        'required' => array_keys($itemProperties),
                    ],
                ],
            ],
            'required' => ['exercises'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'status' => ['type' => 'STRING'],
                'fluency' => ['type' => 'INTEGER'],
                'vocabulary' => ['type' => 'INTEGER'],
                'grammar' => ['type' => 'INTEGER'],
                'confidence' => ['type' => 'INTEGER'],
                'strengths' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
                'mistakes' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING'],
                            'example' => ['type' => 'STRING'],
                            'gentle_correction' => ['type' => 'STRING'],
                        ],
                    ],
                ],
                'dyslexia_support' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
                'recommended_next_step' => ['type' => 'STRING'],
                'feedback' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'title' => ['type' => 'STRING'],
                        'message' => ['type' => 'STRING'],
                    ],
                ],
            ],
            'required' => [
                'score',
                'status',
                'fluency',
                'vocabulary',
                'grammar',
                'confidence',
                'strengths',
                'mistakes',
                'dyslexia_support',
                'recommended_next_step',
                'feedback',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationReplySchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'reply' => ['type' => 'STRING'],
                'language' => ['type' => 'STRING'],
                'gentle_correction' => ['type' => 'STRING'],
                'encouragement' => ['type' => 'STRING'],
                'next_question' => ['type' => 'STRING'],
            ],
            'required' => ['reply', 'language', 'gentle_correction', 'encouragement', 'next_question'],
        ];
    }
}
