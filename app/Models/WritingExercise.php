<?php

namespace App\Models;

use Database\Factories\WritingExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $learning_mode_id
 * @property string $title
 * @property string $prompt
 * @property string|null $instructions
 * @property string $language
 * @property string $level
 * @property int $min_words
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LearningMode|null $learningMode
 */
#[Fillable(['learning_mode_id', 'title', 'prompt', 'instructions', 'language', 'level', 'min_words', 'sort_order', 'is_active'])]
class WritingExercise extends Model
{
    /** @use HasFactory<WritingExerciseFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LearningMode, $this>
     */
    public function learningMode(): BelongsTo
    {
        return $this->belongsTo(LearningMode::class);
    }

    /**
     * @return HasMany<WritingExerciseAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(WritingExerciseAttempt::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'learning_mode_id' => 'integer',
            'min_words' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
