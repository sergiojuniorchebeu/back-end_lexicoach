<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ai_conversation_session_id
 * @property string $role
 * @property string $content
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property AiConversationSession $aiConversationSession
 */
#[Fillable(['ai_conversation_session_id', 'role', 'content', 'metadata'])]
class AiConversationMessage extends Model
{
    public const ROLE_LEARNER = 'learner';

    public const ROLE_ASSISTANT = 'assistant';

    /**
     * @return BelongsTo<AiConversationSession, $this>
     */
    public function aiConversationSession(): BelongsTo
    {
        return $this->belongsTo(AiConversationSession::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
