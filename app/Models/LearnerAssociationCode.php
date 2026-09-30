<?php

namespace App\Models;

use Database\Factories\LearnerAssociationCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $learner_id
 * @property string $code
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $learner
 */
#[Fillable(['learner_id', 'code', 'expires_at', 'used_at', 'cancelled_at'])]
class LearnerAssociationCode extends Model
{
    /** @use HasFactory<LearnerAssociationCodeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->cancelled_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
