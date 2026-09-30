<?php

namespace App\Models;

use Database\Factories\LearningModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'description', 'is_active', 'sort_order'])]
class LearningMode extends Model
{
    public const SLUG_READING = 'reading';

    public const SLUG_WRITING = 'writing';

    public const SLUG_DICTATION = 'dictation';

    public const SLUG_WORD_SPLITTING = 'word-splitting';

    public const SLUG_SMART_ABSTRACT = 'smart-abstract';

    /** @use HasFactory<LearningModeFactory> */
    use HasFactory;

    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return [
            self::SLUG_READING,
            self::SLUG_WRITING,
            self::SLUG_DICTATION,
            self::SLUG_WORD_SPLITTING,
            self::SLUG_SMART_ABSTRACT,
        ];
    }

    /**
     * @return HasMany<ReadingExercise, $this>
     */
    public function readingExercises(): HasMany
    {
        return $this->hasMany(ReadingExercise::class);
    }

    /**
     * @return HasMany<WritingExercise, $this>
     */
    public function writingExercises(): HasMany
    {
        return $this->hasMany(WritingExercise::class);
    }

    /**
     * @return HasMany<SmartAbstractExercise, $this>
     */
    public function smartAbstractExercises(): HasMany
    {
        return $this->hasMany(SmartAbstractExercise::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
