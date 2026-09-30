# Module AI Conversation

Ce module permet au learner de parler avec un coach IA comme dans une vraie conversation.

Le flow final ne met pas la cle Gemini dans Flutter.
Pour la conversation vocale, Flutter se connecte directement a Gemini Live avec un token ephemere cree par Laravel:

```text
Flutter
  -> demande une session a Laravel

Laravel
  -> cree la session pedagogique
  -> demande un token ephemere Gemini
  -> renvoie websocket_url + token + model

Flutter
  -> ouvre le WebSocket Gemini Live direct
  -> envoie le setup Gemini
  -> capture le micro avec record
  -> envoie les chunks PCM a Gemini Live

Flutter
  -> affiche le transcript utilisateur
  -> affiche la reponse assistant
  -> lit la reponse avec flutter_tts
```

Objectif pedagogique:

```text
learner dyslexique
  -> parle naturellement
  -> l IA repond comme un coach patient
  -> l IA pose une seule question a la fois
  -> le backend garde le transcript
  -> le backend evalue les progres en fin de session
  -> Flutter affiche score + feedback + prochaine etape
```

## Configuration

Pour utiliser Gemini:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=ta_cle_gemini
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_LIVE_MODEL=gemini-3.8-live
GEMINI_LIVE_RESPONSE_MODALITY=AUDIO
```

Pour tester sans Gemini:

```env
AI_PROVIDER=fake
```

Pour analyser pourquoi l IA divague:

```env
AI_CONVERSATION_DEBUG_LOGS=true
```

## Limites

Chaque utilisateur possede deux limites:

```text
ai_conversation_session_limit_seconds
ai_conversation_daily_session_limit
```

Valeurs par defaut:

```text
180 secondes par session
3 sessions par jour
```

L admin peut les modifier:

```http
PATCH /api/admin/users/{user}/conversation-limits
```

## Demarrer Une Session

```http
POST /api/ai-conversations
Authorization: Bearer {token_learner}
Accept: application/json
```

Reponse:

```json
{
  "success": true,
  "message": "Session conversation IA creee.",
  "data": {
    "session": {
      "id": 1,
      "status": "active",
      "session_limit_seconds": 180,
      "remaining_seconds": 180
    },
    "quota": {
      "daily_session_limit": 3,
      "daily_sessions_used": 1,
      "daily_sessions_remaining": 2
    }
  }
}
```

## Envoyer Un Tour

Flutter appelle cette route a chaque phrase du learner:

```http
POST /api/ai-conversations/{session}/turn
Authorization: Bearer {token_learner}
Accept: application/json
Content-Type: application/json
```

Body:

```json
{
  "message": "Hello, I want to practice English today."
}
```

Reponse:

```json
{
  "success": true,
  "message": "Tour de conversation IA traite.",
  "data": {
    "learner_message": {
      "role": "learner",
      "content": "Hello, I want to practice English today."
    },
    "assistant_message": {
      "role": "assistant",
      "content": "Great. Tell me one thing you did today."
    },
    "coach": {
      "reply": "Great. Tell me one thing you did today.",
      "gentle_correction": "",
      "encouragement": "Take your time.",
      "next_question": "What did you do today?"
    }
  }
}
```

Le backend garde les derniers messages comme contexte, mais il ne laisse pas Gemini improviser librement.
Avant chaque reponse, Laravel controle le tour de conversation.

## Controle Conversationnel

Le flow backend est volontairement strict:

```text
transcript learner
  -> nettoyage
  -> detection d intention
  -> construction d une tache precise
  -> prompt court et ferme
  -> appel Gemini
  -> nettoyage de la reponse
  -> fallback local si la reponse est mauvaise
  -> reponse audio cote Flutter
```

Les intentions gerees:

```text
readText
explainText
summarize
reformulate
helpWrite
correctText
slowDown
repeat
unclear
generalConversation
```

Regles pour l IA:

- phrases courtes;
- maximum 3 phrases;
- une seule idee a la fois;
- une seule question maximum;
- pas de long paragraphe;
- pas de liste;
- pas de hors sujet;
- une correction principale seulement;
- une action claire ou une question simple a la fin.

Si Gemini repond trop long, sous forme de liste, hors format, ou avec une formule du type `as an AI`, le backend remplace la reponse par un fallback simple.

Exemple de fallback:

```text
Je n ai pas bien compris. Tu veux que je lise, que je resume, ou que j explique ?
```

Les logs backend importants:

```text
AI turn received
AI turn context prepared
Gemini request prepared
Gemini raw response received
Gemini JSON decoded
AI conversation control built
AI conversation reply validated
AI turn reply generated
AI turn saved
```

Ils permettent de voir:

- le transcript exact recu depuis Flutter;
- les derniers messages envoyes comme contexte;
- le prompt exact envoye a Gemini;
- la reponse brute retournee par Gemini;
- l intention detectee;
- la tache envoyee a Gemini;
- la longueur de la reponse brute;
- si le fallback a ete utilise;
- la reponse finale lue par Flutter.

Pendant un test reel, ouvrir un terminal dans `web-api`:

```bash
tail -f storage/logs/laravel.log
```

Cote Flutter, les logs importants commencent par:

```text
AI conversation speech log:
AI conversation USER transcript partial=
AI conversation USER transcript completed=
AI conversation API sendTurn
```

Si l IA divague, verifier dans cet ordre:

1. Le transcript utilisateur est-il correct ?
2. L intention detectee correspond-elle a la demande ?
3. Le prompt envoye a Gemini est-il trop vague ?
4. La reponse brute Gemini est-elle deja mauvaise ?
5. Le validator a-t-il accepte une reponse qu il fallait rejeter ?

## Terminer Une Session

```http
PATCH /api/ai-conversations/{session}/end
Authorization: Bearer {token_learner}
Accept: application/json
```

Si le timer arrive a zero:

```http
PATCH /api/ai-conversations/{session}/expire
```

## Evaluer Les Progres

En fin de session, Flutter appelle:

```http
POST /api/ai-conversations/{session}/assess
```

Le backend demande a l IA une evaluation adaptee a un learner dyslexique.

La reponse contient:

```text
score
status
fluency
vocabulary
grammar
confidence
strengths
mistakes
dyslexia_support
recommended_next_step
feedback
```

La progression conversation est aussi exposee dans:

```http
GET /api/me/progress
```

Sous la cle:

```text
ai-conversation
```

## Flow Mobile

1. Flutter charge `GET /api/auth/me`.
2. Le learner ouvre `AI Conversation`.
3. Flutter appelle `POST /api/ai-conversations`.
4. Laravel renvoie `data.realtime.direct`.
5. Flutter ouvre le WebSocket Gemini direct avec `access_token`.
6. Flutter demarre le micro avec `record`.
7. Flutter envoie le `setup` Gemini:

```json
{
  "setup": {
    "model": "models/gemini-3.8-live",
    "generationConfig": {
      "responseModalities": ["AUDIO"]
    },
    "inputAudioTranscription": {},
    "outputAudioTranscription": {}
  }
}
```

8. Quand le learner appuie sur le micro, Flutter envoie `activityStart`.
9. Flutter envoie les chunks PCM dans `realtimeInput.audio`.
10. Quand le learner arrete de parler, Flutter envoie `activityEnd`.
11. Gemini Live renvoie `inputTranscription`.
12. Gemini Live renvoie la reponse assistant.
13. Flutter affiche la reponse et la lit avec `flutter_tts`.
14. A la fin, Flutter appelle `end` puis `assess`.

## Test Reel Dans L App Mobile

1. Se connecter avec un compte learner.
2. Ouvrir `Learning Modes`.
3. Ouvrir `AI Conversation`.
4. Appuyer sur `Start session`.
5. Appuyer sur le gros bouton micro.
6. Dire:

```text
Hello, my name is Anna. I want to practice English today.
```

7. Appuyer encore sur le micro pour arreter.
8. Verifier que la phrase apparait dans le transcript.
9. Ecouter la reponse de l IA.
10. Continuer comme une discussion normale.
11. Terminer la session pour voir le score.

## Important

Ce module utilise maintenant Gemini Live pour la capture vocale realtime.
La lecture audio finale reste faite par `flutter_tts` a partir du texte assistant retourne par Gemini Live.

Le proxy Node reste dans le repo comme option de secours, mais l ecran mobile AI Conversation utilise maintenant la connexion directe Gemini Live avec token ephemere.

Le vieux flow `speech_to_text -> POST /turn -> TTS` reste disponible cote API, mais il n est plus le flow principal de l ecran mobile.
