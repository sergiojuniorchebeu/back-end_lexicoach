<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $session_limit_seconds
 * @property string $status
 * @property Carbon $started_at
 * @property Carbon $expires_at
 * @property Carbon|null $ended_at
 * @property int|null $assessment_score
 * @property string|null $assessment_status
 * @property array<string, mixed>|null $assessment_feedback
 * @property Carbon|null $assessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 */
#[Fillable([
    'user_id',
    'session_limit_seconds',
    'status',
    'started_at',
    'expires_at',
    'ended_at',
    'assessment_score',
    'assessment_status',
    'assessment_feedback',
    'assessed_at',
])]
class AiConversationSession extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AiConversationMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiConversationMessage::class)->oldest();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_limit_seconds' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
            'assessment_score' => 'integer',
            'assessment_feedback' => 'array',
            'assessed_at' => 'datetime',
        ];
    }
}
