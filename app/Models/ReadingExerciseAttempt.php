<?php

namespace App\Models;

use Database\Factories\ReadingExerciseAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $reading_exercise_id
 * @property string $transcript
 * @property int $score
 * @property string $status
 * @property bool $is_correct
 * @property array<int, array<string, string|null>> $words
 * @property array<string, string> $feedback
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 * @property ReadingExercise $readingExercise
 */
#[Fillable(['user_id', 'reading_exercise_id', 'transcript', 'score', 'status', 'is_correct', 'words', 'feedback'])]
class ReadingExerciseAttempt extends Model
{
    /** @use HasFactory<ReadingExerciseAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ReadingExercise, $this>
     */
    public function readingExercise(): BelongsTo
    {
        return $this->belongsTo(ReadingExercise::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'is_correct' => 'boolean',
            'words' => 'array',
            'feedback' => 'array',
        ];
    }
}
