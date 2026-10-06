<?php

namespace App\Models;

use Database\Factories\SmartAbstractExerciseFactory;
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
 * @property string $source_text
 * @property string|null $instructions
 * @property string $language
 * @property string $level
 * @property int $min_words
 * @property int|null $max_words
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LearningMode|null $learningMode
 */
#[Fillable(['learning_mode_id', 'title', 'source_text', 'instructions', 'language', 'level', 'min_words', 'max_words', 'sort_order', 'is_active'])]
class SmartAbstractExercise extends Model
{
    /** @use HasFactory<SmartAbstractExerciseFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LearningMode, $this>
     */
    public function learningMode(): BelongsTo
    {
        return $this->belongsTo(LearningMode::class);
    }

    /**
     * @return HasMany<SmartAbstractAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(SmartAbstractAttempt::class);
    }

    /**
     * @return HasMany<SmartAbstractPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SmartAbstractPayment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'learning_mode_id' => 'integer',
            'min_words' => 'integer',
            'max_words' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
