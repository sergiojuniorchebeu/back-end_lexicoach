# Module Writing Assistant / Smart Abstract avec Gemini

Ce module ajoute deux fonctionnalites IA cote backend:

- `Writing Assistant`: l apprenante ecrit une reponse, le backend demande a Gemini de corriger et d evaluer le texte.
- `Smart Abstract`: l apprenante resume un texte, le backend demande a Gemini d evaluer le resume.

Flutter ne parle jamais directement a Gemini. Flutter appelle seulement l API Laravel.

## Configuration IA

Par defaut, le projet utilise un faux moteur IA local pour les tests:

```env
AI_PROVIDER=fake
```

Pour tester avec Gemini:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=ta_cle_api_gemini
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_TIMEOUT=30
```

Apres modification du fichier `.env`, lancer:

```bash
php artisan config:clear
```

## Scenario 1 - Writing Assistant

Objectif: l apprenante ecrit un petit texte et recoit une correction douce.

Parcours frontend:

1. L utilisatrice choisit le mode `writing`.
2. Flutter appelle `GET /api/learning-modes/writing/exercises`.
3. Flutter affiche `title`, `prompt`, `instructions`, `min_words`.
4. L apprenante ecrit sa reponse dans un champ texte.
5. Flutter appelle `POST /api/writing-exercises/{id}/evaluate`.
6. Le backend appelle Gemini, calcule le resultat et sauvegarde une tentative.
7. Flutter affiche le score, le texte corrige, les erreurs et les conseils.

Route principale:

```http
POST /api/writing-exercises/{writingExercise}/evaluate
```

Body:

```json
{
  "answer": "Today I went to school and learned a new English word with my teacher."
}
```

Reponse:

```json
{
  "success": true,
  "message": "Texte writing evalue.",
  "data": {
    "result": {
      "score": 82,
      "status": "good",
      "answer": "Today I went to school...",
      "corrected_text": "Today I went to school...",
      "mistakes": [],
      "suggestions": ["Add one clear example."],
      "feedback": {
        "title": "Good work!",
        "message": "Your answer is understandable. Improve details and punctuation."
      }
    },
    "attempt": {
      "id": 1,
      "score": 82,
      "status": "good",
      "created_at": "2026-09-03T10:00:00.000000Z"
    }
  }
}
```

## Scenario 2 - Smart Abstract

Objectif: l apprenante lit un texte puis produit un resume court.

Parcours frontend:

1. L utilisatrice choisit le mode `smart-abstract`.
2. Flutter appelle `GET /api/learning-modes/smart-abstract/exercises`.
3. Flutter affiche `source_text`, `instructions`, `min_words`, `max_words`.
4. L apprenante ecrit son resume.
5. Flutter appelle `POST /api/smart-abstract-exercises/{id}/evaluate`.
6. Le backend appelle Gemini, verifie si les idees importantes sont presentes et sauvegarde une tentative.
7. Flutter affiche le score, le resume ameliore, les idees manquantes et le feedback.

Route principale:

```http
POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate
```

Body:

```json
{
  "summary": "The class visits the library and the teacher helps learners choose books."
}
```

Reponse:

```json
{
  "success": true,
  "message": "Resume intelligent evalue.",
  "data": {
    "result": {
      "score": 84,
      "status": "good",
      "summary": "The class visits the library...",
      "improved_summary": "The class visits the library...",
      "missing_ideas": [],
      "strengths": ["The summary is readable."],
      "feedback": {
        "title": "Good summary!",
        "message": "The summary keeps the main idea. Make it even more precise."
      }
    },
    "attempt": {
      "id": 1,
      "score": 84,
      "status": "good",
      "created_at": "2026-09-03T10:00:00.000000Z"
    }
  }
}
```

## Routes ajoutees

Learner:

```http
GET  /api/writing-exercises
GET  /api/writing-exercises/{writingExercise}
POST /api/writing-exercises/{writingExercise}/evaluate
GET  /api/me/writing-attempts
GET  /api/me/writing-progress

GET  /api/smart-abstract-exercises
GET  /api/smart-abstract-exercises/{smartAbstractExercise}
POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate
GET  /api/me/smart-abstract-attempts
GET  /api/me/smart-abstract-progress
```

Learning modes:

```http
GET /api/learning-modes/writing/exercises
GET /api/learning-modes/smart-abstract/exercises
```

Tutor:

```http
GET /api/tutor/learners/{learner}/writing-attempts
GET /api/tutor/learners/{learner}/smart-abstract-attempts
```

Progression globale:

```http
GET /api/me/progress
```

Cette route retourne maintenant les vrais scores pour:

- `reading`
- `writing`
- `smart-abstract`

## Ce que Flutter doit retenir

- Flutter affiche les exercices.
- Flutter envoie seulement le texte de l apprenante.
- Laravel garde l exercice officiel en base.
- Laravel appelle Gemini.
- Laravel sauvegarde la tentative.
- Flutter affiche `score`, `status`, `feedback` et les details utiles.

## Erreur IA

Si `AI_PROVIDER=gemini` mais que la cle est absente ou invalide, l API retourne:

```json
{
  "success": false,
  "message": "Service IA indisponible.",
  "errors": {
    "ai": ["GEMINI_API_KEY is missing."]
  }
}
```

Dans Flutter, il faut afficher un message simple, par exemple:

```text
La correction IA est indisponible pour le moment. Reessaie plus tard.
```
