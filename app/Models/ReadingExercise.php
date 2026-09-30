<?php

namespace App\Models;

use Database\Factories\ReadingExerciseFactory;
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
 * @property string $text
 * @property string $language
 * @property string $level
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property LearningMode|null $learningMode
 */
#[Fillable(['learning_mode_id', 'title', 'text', 'language', 'level', 'sort_order', 'is_active'])]
class ReadingExercise extends Model
{
    /** @use HasFactory<ReadingExerciseFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LearningMode, $this>
     */
    public function learningMode(): BelongsTo
    {
        return $this->belongsTo(LearningMode::class);
    }

    /**
     * @return HasMany<ReadingExerciseAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ReadingExerciseAttempt::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'learning_mode_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
