<?php

namespace App\Models;

use Database\Factories\WritingExerciseAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $writing_exercise_id
 * @property string $answer
 * @property int $score
 * @property string $status
 * @property string $corrected_text
 * @property array<int, mixed> $mistakes
 * @property array<int, mixed> $suggestions
 * @property array<string, string> $feedback
 * @property array<string, mixed>|null $raw_ai_response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 * @property WritingExercise $writingExercise
 */
#[Fillable(['user_id', 'writing_exercise_id', 'answer', 'score', 'status', 'corrected_text', 'mistakes', 'suggestions', 'feedback', 'raw_ai_response'])]
class WritingExerciseAttempt extends Model
{
    /** @use HasFactory<WritingExerciseAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WritingExercise, $this>
     */
    public function writingExercise(): BelongsTo
    {
        return $this->belongsTo(WritingExercise::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'mistakes' => 'array',
            'suggestions' => 'array',
            'feedback' => 'array',
            'raw_ai_response' => 'array',
        ];
    }
}
