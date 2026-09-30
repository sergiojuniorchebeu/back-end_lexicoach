<?php

namespace App\Services\Ai\Conversation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConversationControlService
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function buildControl(array $messages): array
    {
        $latestUserText = $this->latestUserText($messages);
        $intent = $this->detectIntent($latestUserText);
        $state = $this->conversationState($messages, $intent);
        $task = $this->taskFor($intent);
        $prompt = $this->promptFor(
            intent: $intent,
            task: $task,
            userTranscript: $latestUserText,
            state: $state,
        );

        Log::info('AI conversation control built', [
            'intent' => $intent->value,
            'task' => $task,
            'last_topic' => $state['last_topic'],
            'user_transcript' => $latestUserText,
            'prompt' => $prompt,
        ]);

        return [
            'intent' => $intent->value,
            'task' => $task,
            'prompt' => $prompt,
            'state' => $state,
            'latest_user_text' => $latestUserText,
        ];
    }

    /**
     * @param  array<string, mixed>  $rawReply
     * @param  array<string, mixed>  $control
     * @return array<string, mixed>
     */
    public function cleanOrFallback(array $rawReply, array $control): array
    {
        $intent = AssistantIntent::tryFrom((string) ($control['intent'] ?? ''))
            ?? AssistantIntent::Unclear;
        $reply = $this->sanitizeAssistantReply((string) ($rawReply['reply'] ?? ''));
        $rejectionReason = $this->badReplyReason($reply, $control);
        $fallback = false;

        if ($rejectionReason !== null) {
            $fallback = true;
            $reply = $this->fallbackFor($intent, (string) ($control['latest_user_text'] ?? ''));
        }

        Log::info('AI conversation reply validated', [
            'intent' => $intent->value,
            'raw_length' => mb_strlen((string) ($rawReply['reply'] ?? '')),
            'raw_reply' => (string) ($rawReply['reply'] ?? ''),
            'reply_accepted' => ! $fallback,
            'reply_fallback' => $fallback,
            'rejection_reason' => $rejectionReason,
            'assistant_reply' => $reply,
        ]);

        return [
            ...$rawReply,
            'reply' => $reply,
            'intent' => $intent->value,
            'task' => $control['task'] ?? null,
            'fallback_used' => $fallback,
            'fallback_reason' => $rejectionReason,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     */
    private function latestUserText(array $messages): string
    {
        $latest = collect($messages)
            ->where('role', 'learner')
            ->pluck('content')
            ->last();

        return is_string($latest) ? trim($latest) : '';
    }

    private function detectIntent(string $text): AssistantIntent
    {
        $lower = Str::lower($text);

        return match (true) {
            $text === '' => AssistantIntent::Unclear,
            Str::contains($lower, ['stop', 'arrête', 'arrete', 'pause']) => AssistantIntent::Repeat,
            Str::contains($lower, ['repeat', 'répète', 'repete', 'again', 'recommence']) => AssistantIntent::Repeat,
            Str::contains($lower, ['slow', 'lentement', 'slower', 'doucement']) => AssistantIntent::SlowDown,
            Str::contains($lower, ['read', 'lis ', 'lire', 'lecture']) => AssistantIntent::ReadText,
            Str::contains($lower, [
                'explain',
                'explique',
                'comprendre',
                'understand',
                'meaning',
                'parle-moi',
                'parle moi',
                'dis-moi',
                'dis moi',
                'tell me about',
                'talk about',
                'what is',
                'c est quoi',
                "c'est quoi",
            ]) => AssistantIntent::ExplainText,
            Str::contains($lower, ['summary', 'summarize', 'résume', 'resume', 'résumé']) => AssistantIntent::Summarize,
            Str::contains($lower, ['reformule', 'rephrase', 'simplifie', 'simplify']) => AssistantIntent::Reformulate,
            Str::contains($lower, ['write', 'écrire', 'ecrire', 'rédige', 'redige']) => AssistantIntent::HelpWrite,
            Str::contains($lower, ['correct', 'corrige', 'mistake', 'erreur']) => AssistantIntent::CorrectText,
            Str::contains($lower, ["i don't understand", 'je ne comprends pas', 'pas compris']) => AssistantIntent::Unclear,
            default => AssistantIntent::GeneralConversation,
        };
    }

    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    private function conversationState(array $messages, AssistantIntent $intent): array
    {
        $lastAssistant = collect($messages)
            ->where('role', 'assistant')
            ->pluck('content')
            ->last();
        $lastUser = $this->latestUserText($messages);

        return [
            'last_topic' => $this->shortTopic($lastUser),
            'last_intent' => $intent->value,
            'last_assistant_answer' => is_string($lastAssistant) ? Str::limit($lastAssistant, 180, '') : null,
            'user_preference' => 'short simple sentences',
            'user_needs_simple_words' => true,
            'should_speak_slowly' => in_array($intent, [AssistantIntent::SlowDown, AssistantIntent::Unclear], true),
            'current_document_summary' => null,
        ];
    }

    private function shortTopic(string $text): string
    {
        if ($text === '') {
            return 'general voice practice';
        }

        return Str::limit($text, 90, '');
    }

    private function taskFor(AssistantIntent $intent): string
    {
        return match ($intent) {
            AssistantIntent::ReadText => 'Read or guide the requested text in a simple way.',
            AssistantIntent::ExplainText => 'Answer the exact user request with a simple explanation about the requested topic.',
            AssistantIntent::Summarize => 'Give a very short summary.',
            AssistantIntent::Reformulate => 'Reformulate with simpler words.',
            AssistantIntent::HelpWrite => 'Propose one simple sentence the learner can use.',
            AssistantIntent::CorrectText => 'Correct only the main error and give the corrected version.',
            AssistantIntent::SlowDown => 'Repeat more slowly with very short sentences.',
            AssistantIntent::Repeat => 'Repeat the last useful answer briefly.',
            AssistantIntent::Unclear => 'Ask one simple clarification question.',
            AssistantIntent::GeneralConversation => 'Answer the learner message directly, then ask one simple follow-up question.',
        };
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function promptFor(
        AssistantIntent $intent,
        string $task,
        string $userTranscript,
        array $state,
    ): string {
        return <<<PROMPT
Tu es un assistant vocal pour une personne dyslexique.
Tu dois aider a lire, comprendre, reformuler, ecrire ou resumer.

Regles absolues:
1. Reponds avec des phrases courtes.
2. Une seule idee a la fois.
3. Maximum 3 phrases.
4. Ne change pas de sujet.
5. Ne donne pas d'informations non demandees.
6. Si la demande est floue, pose une seule question de clarification.
7. Utilise un ton calme, clair et encourageant.
8. Si tu corriges, explique seulement la correction principale.
9. Termine par une action claire ou une question simple.
10. Ne repete pas une salutation precedente si l utilisateur demande un sujet.
11. Si l utilisateur dit "parle-moi de X", explique X directement.

Memoire courte:
- Dernier sujet: {$state['last_topic']}
- Derniere intention: {$state['last_intent']}
- Derniere reponse assistant: {$state['last_assistant_answer']}
- Preference: phrases simples et courtes

Tour actuel:
- Intention detectee: {$intent->value}
- Demande utilisateur: "{$userTranscript}"

Tache exacte:
{$task}

Contraintes:
- Maximum 3 phrases.
- Une seule question maximum.
- Pas de liste.
- Pas de paragraphe long.
- Pas de formule comme "en tant que".
- Ne reponds pas par une simple salutation si la demande contient un sujet.

Retourne uniquement le JSON demande.
PROMPT;
    }

    private function sanitizeAssistantReply(string $reply): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', str_replace(["\n", "\r"], ' ', $reply)) ?? '');
        $sentences = preg_split('/(?<=[.!?])\s+/', $cleaned) ?: [];

        return trim(implode(' ', array_slice(array_filter($sentences), 0, 3)));
    }

    /**
     * @param  array<string, mixed>  $control
     */
    private function badReplyReason(string $text, array $control): ?string
    {
        $lower = Str::lower($text);
        $latestUserText = Str::lower((string) ($control['latest_user_text'] ?? ''));
        $lastAssistantAnswer = Str::lower((string) data_get($control, 'state.last_assistant_answer', ''));

        if ($text === '') return 'empty';
        if (mb_strlen($text) > 350) return 'too_long';
        if (substr_count($text, '?') > 1) return 'too_many_questions';
        if (preg_match('/(^|\s)(1\.|2\.|3\.|- )/', $text) === 1) return 'looks_like_list';
        if (Str::contains($lower, ['en tant qu', 'as an ai', 'language model'])) return 'model_disclaimer';
        if ($lastAssistantAnswer !== '' && $this->normalizeForComparison($lower) === $this->normalizeForComparison($lastAssistantAnswer)) return 'repeated_previous_answer';
        if ($this->userAskedForSpecificTopic($latestUserText) && $this->isGenericGreeting($lower)) return 'generic_greeting_for_specific_topic';

        return null;
    }

    private function fallbackFor(AssistantIntent $intent, string $latestUserText): string
    {
        $topic = $this->topicFromUserText($latestUserText);

        return match ($intent) {
            AssistantIntent::ExplainText => $topic !== ''
                ? "Je vais faire simple. {$topic} est un sujet que je peux expliquer avec des mots faciles."
                : 'Je vais faire simple. Dis-moi le sujet que tu veux comprendre.',
            AssistantIntent::Summarize => 'Sure. I can summarize it in one simple sentence.',
            AssistantIntent::Reformulate => 'Sure. I can reformulate it with simpler words.',
            AssistantIntent::HelpWrite => 'I can help. Tell me the sentence you want to write.',
            AssistantIntent::CorrectText => 'I can correct one thing at a time. Which sentence do you want to correct?',
            AssistantIntent::SlowDown => 'Sure. I will speak more slowly.',
            AssistantIntent::Repeat => 'Sure. I will start again simply.',
            default => 'I did not understand clearly. Do you want me to read, summarize, or explain?',
        };
    }

    private function userAskedForSpecificTopic(string $text): bool
    {
        return Str::contains($text, [
            'parle-moi',
            'parle moi',
            'dis-moi',
            'dis moi',
            'explique',
            'tell me about',
            'talk about',
            'what is',
            'c est quoi',
            "c'est quoi",
        ]);
    }

    private function isGenericGreeting(string $text): bool
    {
        return Str::contains($text, [
            'comment puis-je t',
            'comment je peux t',
            'how can i help',
            'what can i help',
        ]);
    }

    private function normalizeForComparison(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9àâçéèêëîïôûùüÿñæœ]+/iu', ' ', $text) ?? '');
    }

    private function topicFromUserText(string $text): string
    {
        $cleaned = trim($text);
        $patterns = [
            '/^parle[- ]moi de\s+/iu',
            '/^dis[- ]moi de\s+/iu',
            '/^explique[- ]moi\s+/iu',
            '/^tell me about\s+/iu',
            '/^talk about\s+/iu',
            '/^what is\s+/iu',
            '/^c[’\']?est quoi\s+/iu',
        ];

        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        return trim($cleaned, " \t\n\r\0\x0B.?!,:;'");
    }
}
