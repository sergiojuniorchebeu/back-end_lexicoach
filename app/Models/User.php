<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string $preferred_language
 * @property string $learning_level
 * @property int $dyslexia_font_size
 * @property bool $dyslexia_slow_speech
 * @property int $ai_conversation_session_limit_seconds
 * @property int $ai_conversation_daily_session_limit
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'preferred_language',
    'learning_level',
    'dyslexia_font_size',
    'dyslexia_slow_speech',
    'ai_conversation_session_limit_seconds',
    'ai_conversation_daily_session_limit',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_LEARNER = 'learner';

    public const ROLE_TUTOR = 'tutor';

    public const ROLE_ADMIN = 'admin';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_LEARNER,
            self::ROLE_TUTOR,
            self::ROLE_ADMIN,
        ];
    }

    /**
     * @return HasMany<ReadingExerciseAttempt, $this>
     */
    public function readingExerciseAttempts(): HasMany
    {
        return $this->hasMany(ReadingExerciseAttempt::class);
    }

    /**
     * @return HasMany<WritingExerciseAttempt, $this>
     */
    public function writingExerciseAttempts(): HasMany
    {
        return $this->hasMany(WritingExerciseAttempt::class);
    }

    /**
     * @return HasMany<SmartAbstractAttempt, $this>
     */
    public function smartAbstractAttempts(): HasMany
    {
        return $this->hasMany(SmartAbstractAttempt::class);
    }

    /**
     * @return HasMany<AiConversationSession, $this>
     */
    public function aiConversationSessions(): HasMany
    {
        return $this->hasMany(AiConversationSession::class);
    }

    /**
     * @return HasMany<LearnerAssociationCode, $this>
     */
    public function learnerAssociationCodes(): HasMany
    {
        return $this->hasMany(LearnerAssociationCode::class, 'learner_id');
    }

    /**
     * Learners linked to this tutor.
     *
     * @return BelongsToMany<User, $this>
     */
    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_learners', 'tutor_id', 'learner_id')
            ->withTimestamps();
    }

    /**
     * Tutors linked to this learner.
     *
     * @return BelongsToMany<User, $this>
     */
    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_learners', 'learner_id', 'tutor_id')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dyslexia_font_size' => 'integer',
            'dyslexia_slow_speech' => 'boolean',
            'ai_conversation_session_limit_seconds' => 'integer',
            'ai_conversation_daily_session_limit' => 'integer',
        ];
    }
}
