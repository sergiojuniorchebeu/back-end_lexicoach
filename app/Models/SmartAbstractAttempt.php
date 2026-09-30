<?php

namespace App\Models;

use Database\Factories\SmartAbstractAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $smart_abstract_exercise_id
 * @property string $summary
 * @property int $score
 * @property string $status
 * @property string $improved_summary
 * @property array<int, mixed> $missing_ideas
 * @property array<int, mixed> $strengths
 * @property array<string, string> $feedback
 * @property array<string, mixed>|null $raw_ai_response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 * @property SmartAbstractExercise $smartAbstractExercise
 */
#[Fillable(['user_id', 'smart_abstract_exercise_id', 'summary', 'score', 'status', 'improved_summary', 'missing_ideas', 'strengths', 'feedback', 'raw_ai_response'])]
class SmartAbstractAttempt extends Model
{
    /** @use HasFactory<SmartAbstractAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SmartAbstractExercise, $this>
     */
    public function smartAbstractExercise(): BelongsTo
    {
        return $this->belongsTo(SmartAbstractExercise::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'missing_ideas' => 'array',
            'strengths' => 'array',
            'feedback' => 'array',
            'raw_ai_response' => 'array',
        ];
    }
}
