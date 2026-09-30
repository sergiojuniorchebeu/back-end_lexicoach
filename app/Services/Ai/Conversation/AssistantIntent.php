<?php

namespace App\Services\Ai\Conversation;

enum AssistantIntent: string
{
    case ReadText = 'readText';
    case ExplainText = 'explainText';
    case Summarize = 'summarize';
    case Reformulate = 'reformulate';
    case HelpWrite = 'helpWrite';
    case CorrectText = 'correctText';
    case SlowDown = 'slowDown';
    case Repeat = 'repeat';
    case Unclear = 'unclear';
    case GeneralConversation = 'generalConversation';
}
