<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $smart_abstract_exercise_id
 * @property string $checkout_reference
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 * @property SmartAbstractExercise $smartAbstractExercise
 */
#[Fillable([
    'user_id',
    'smart_abstract_exercise_id',
    'checkout_reference',
    'amount',
    'currency',
    'status',
    'consumed_at',
])]
class SmartAbstractPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

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
            'user_id' => 'integer',
            'smart_abstract_exercise_id' => 'integer',
            'amount' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }
}
