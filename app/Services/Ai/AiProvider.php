<?php

namespace App\Services\Ai;

use App\Models\SmartAbstractExercise;
use App\Models\WritingExercise;

interface AiProvider
{
    /**
     * @return array<string, mixed>
     */
    public function evaluateWriting(WritingExercise $exercise, string $answer): array;

    /**
     * @return array<string, mixed>
     */
    public function evaluateSmartAbstract(SmartAbstractExercise $exercise, string $documentText): array;

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function evaluateConversation(array $messages): array;

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $control
     * @return array<string, mixed>
     */
    public function replyToConversation(array $messages, array $control): array;
}
