# Module Reading Exercise

Ce document explique le module backend pour l exercice de lecture avec speech-to-text et text-to-speech.

Le code reste volontairement simple pour une etudiante:

- Laravel fournit les modes d apprentissage disponibles
- Flutter affiche le texte a lire
- Flutter utilise `flutter_tts` pour faire lire le texte par le telephone
- Flutter utilise `speech_to_text` pour transformer la voix de l utilisateur en texte
- Laravel recoit le texte reconnu, le compare avec le texte officiel, puis retourne un score et un feedback
- Laravel sauvegarde chaque tentative pour construire la progression de l apprenante

Important: le backend ne transforme pas l audio en texte dans cette version MVP. Le telephone fait deja cette partie.

## Objectif

Quand l utilisateur ouvre Reading Practice:

1. L application recupere les learning modes depuis le backend.
2. L utilisatrice choisit le mode `reading`.
3. L application recupere les exercices lies au mode `reading`.
4. Elle affiche le texte a lire.
5. Le bouton Listen utilise le text-to-speech cote Flutter.
6. Le bouton micro utilise le speech-to-text cote Flutter.
7. Flutter envoie le transcript au backend.
8. Laravel compare le transcript avec le texte attendu.
9. Laravel sauvegarde la tentative.
10. Laravel retourne les mots corrects, manquants, incorrects ou en trop.
11. Flutter peut recuperer la progression du learner plus tard.

## Scenarios de la fonctionnalite

Cette partie sert a comprendre le parcours complet avant de coder le frontend.

### Scenario 0 - Afficher les learning modes

L utilisatrice ouvre l ecran `Practice Exercises`.

Le frontend doit appeler:

```http
GET /api/learning-modes
Authorization: Bearer {access_token}
Accept: application/json
```

Le backend retourne les modes actifs:

```text
reading
writing
dictation
word-splitting
smart-abstract
```

Les modes `reading`, `writing` et `smart-abstract` sont maintenant operationnels jusqu a l evaluation et la progression. Les autres modes restent prepares pour les prochains modules.

### Scenario 1 - Ouvrir Reading Practice

L utilisatrice clique sur `Practice Exercises`, puis choisit `Reading`.

Ce que le frontend doit faire:

1. Verifier que l utilisatrice est connectee.
2. Recuperer le token sauvegarde apres login ou register.
3. Appeler l API pour charger les exercices du mode reading:

```http
GET /api/learning-modes/reading/exercises
Authorization: Bearer {access_token}
Accept: application/json
```

4. Afficher un loader pendant le chargement.
5. Recuperer la liste dans `data.exercises`.
6. Afficher le premier exercice ou laisser l utilisatrice choisir un exercice.

Ce que le frontend doit garder en memoire:

- `exercise.id`: utile pour appeler l evaluation
- `exercise.text`: le texte que l utilisatrice doit lire
- `exercise.language`: utile pour choisir la langue du TTS et du STT
- `exercise.level`: utile pour afficher le niveau

### Scenario 2 - Afficher le texte a lire

Une fois l exercice charge, l application affiche le texte officiel.

Exemple:

```text
The children visited the beautiful museum yesterday.
```

Ce que le frontend doit faire:

1. Afficher `exercise.text` clairement.
2. Ne pas modifier le texte avant de l afficher.
3. Prevoir un bouton `Listen`.
4. Prevoir un bouton micro.
5. Prevoir une zone `You said` pour afficher le transcript reconnu.

Important: le texte officiel vient du backend. Le frontend ne doit pas inventer le texte lui-meme.

### Scenario 3 - Ecouter la bonne prononciation

L utilisatrice appuie sur `Listen`.

Ce que le frontend doit faire:

1. Utiliser le package `flutter_tts`.
2. Utiliser `exercise.language`, par exemple `en-US`.
3. Lire `exercise.text`.
4. Desactiver temporairement le bouton pendant la lecture si necessaire.
5. Ajouter plus tard une option `Slow` pour lire plus lentement.

Exemple d intention cote Flutter:

```dart
await flutterTts.setLanguage(exercise.language);
await flutterTts.setSpeechRate(0.45);
await flutterTts.speak(exercise.text);
```

Il n y a pas d appel backend pour cette partie dans le MVP.

### Scenario 4 - Parler avec le micro

L utilisatrice appuie sur le bouton micro et lit la phrase a voix haute.

Ce que le frontend doit faire:

1. Demander la permission microphone.
2. Utiliser le service Flutter `SpeechCaptureService`.
3. Le service utilise le package `speech_to_text`.
4. Utiliser `exercise.language` pour la reconnaissance vocale.
5. Afficher un etat `Listening...` pendant que le micro ecoute.
6. Afficher le texte reconnu dans la zone `You said`.
7. Garder le meilleur transcript final en memoire.

Pourquoi on passe par `SpeechCaptureService`:

- le moteur vocal natif peut couper la connexion;
- les resultats peuvent arriver en plusieurs morceaux;
- iOS ne renvoie pas toujours `finalResult`;
- le service garde le meilleur transcript recu;
- le service attend une petite garde avant d envoyer l evaluation;
- le service evite de perdre la phrase si une erreur arrive apres quelques mots.

Le frontend ne doit pas lancer directement toute la logique dans la page.
La page doit seulement appeler:

```dart
await speechCapture.start(
  localeId: exercise.language,
  listenFor: const Duration(seconds: 45),
  pauseFor: const Duration(seconds: 4),
);
```

Puis attendre les callbacks:

```text
onListeningChanged -> changer l etat du bouton micro
onTranscript        -> afficher le texte reconnu
onCompleted         -> appeler l API d evaluation
onError             -> afficher un message simple
```

Exemple:

```text
You said:
The children visited beautiful museum yesterday.
```

Il n y a pas d upload audio dans cette version. Le frontend enverra seulement le transcript au backend.

### Scenario 5 - Evaluer la lecture

Quand le transcript existe, l utilisatrice appuie sur `Check` ou l application lance l evaluation automatiquement.

Ce que le frontend doit envoyer:

```http
POST /api/reading-exercises/{id}/evaluate
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

Body:

```json
{
    "transcript": "The children visited beautiful museum yesterday."
}
```

Important: le frontend ne doit pas envoyer `expected_text`.

Pourquoi ?

Parce que le backend connait deja le texte officiel grace a `exercise.id`. Le backend est la source de verite.

Ce que le frontend doit lire dans la reponse:

- `data.result.score`: le score en pourcentage
- `data.result.status`: le niveau du resultat
- `data.result.is_correct`: vrai ou faux
- `data.result.words`: la comparaison mot par mot
- `data.result.feedback.title`: le titre du feedback
- `data.result.feedback.message`: le message a afficher

### Scenario 6 - Afficher le resultat

Apres l evaluation, l application affiche le score et le feedback.

Si la lecture est correcte:

```text
Excellent!
100%
You read the sentence correctly.
```

Si des mots manquent:

```text
Good job!
86%
You read most of the sentence correctly. Try again and focus on the highlighted words.
```

Ce que le frontend doit faire avec `words`:

- afficher les mots `correct` normalement ou en vert doux
- afficher les mots `missing` comme manquants
- afficher les mots `incorrect` avec une indication claire
- afficher les mots `extra` comme mots en trop

Exemple simple:

```text
The       correct
children  correct
visited   correct
the       missing
beautiful correct
```

### Scenario 7 - Reessayer

Si le score n est pas parfait, l utilisatrice peut reessayer.

Ce que le frontend doit faire:

1. Garder le meme `exercise.id`.
2. Effacer l ancien transcript.
3. Remettre le bouton micro disponible.
4. Laisser l utilisatrice ecouter encore une fois avec `Listen`.
5. Envoyer le nouveau transcript au meme endpoint `evaluate`.

Cette logique permet un parcours proche de Duolingo:

```text
Listen -> Speak -> Check -> Feedback -> Try again
```

### Scenario 8 - Voir la progression de lecture

Chaque appel a `evaluate` sauvegarde une tentative.

Le frontend peut ensuite afficher la progression dans le dashboard ou dans le profil.

Pour recuperer le resume:

```http
GET /api/me/reading-progress
Authorization: Bearer {access_token}
Accept: application/json
```

Pour recuperer l historique complet:

```http
GET /api/me/reading-attempts
Authorization: Bearer {access_token}
Accept: application/json
```

Ce que le frontend peut afficher:

- nombre total de tentatives
- nombre d exercices termines parfaitement
- score moyen
- meilleur score
- derniere tentative
- historique des anciens essais

## Routes disponibles

Base URL locale:

```text
http://localhost:8000/api
```

Toutes les routes de ce module demandent un token Sanctum:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

Toutes les routes de ce module demandent aussi le role:

```text
learner
```

Donc il y a deux controles:

1. `auth:sanctum`: l utilisateur doit etre connecte.
2. `role:learner`: l utilisateur connecte doit etre un apprenant.

Si le token est absent, Laravel retourne `401 Unauthenticated`.

Si le token existe mais que le role n est pas `learner`, Laravel retourne `403 Forbidden`.

### Liste des learning modes

```http
GET /api/learning-modes
```

Reponse:

```json
{
    "success": true,
    "message": "Modes d apprentissage recuperes.",
    "data": {
        "learning_modes": [
            {
                "id": 1,
                "name": "Reading Practice",
                "slug": "reading",
                "description": "Read a sentence aloud, compare your transcript and improve fluency.",
                "is_active": true,
                "sort_order": 1
            }
        ]
    }
}
```

### Exercices du mode reading

```http
GET /api/learning-modes/reading/exercises
```

Reponse:

```json
{
    "success": true,
    "message": "Exercices du mode reading recuperes.",
    "data": {
        "learning_mode": {
            "id": 1,
            "name": "Reading Practice",
            "slug": "reading",
            "description": "Read a sentence aloud, compare your transcript and improve fluency.",
            "is_active": true,
            "sort_order": 1
        },
        "exercises": [
            {
                "id": 1,
                "title": "Museum visit",
                "text": "The children visited the beautiful museum yesterday.",
                "language": "en-US",
                "level": "beginner"
            }
        ]
    }
}
```

### Liste des exercices

```http
GET /api/reading-exercises
```

Reponse:

```json
{
    "success": true,
    "message": "Exercices de lecture recuperes.",
    "data": {
        "exercises": [
            {
                "id": 1,
                "title": "Museum visit",
                "text": "The children visited the beautiful museum yesterday.",
                "language": "en-US",
                "level": "beginner"
            }
        ]
    }
}
```

### Detail d un exercice

```http
GET /api/reading-exercises/1
```

Reponse:

```json
{
    "success": true,
    "message": "Exercice de lecture recupere.",
    "data": {
        "exercise": {
            "id": 1,
            "title": "Museum visit",
            "text": "The children visited the beautiful museum yesterday.",
            "language": "en-US",
            "level": "beginner"
        }
    }
}
```

### Evaluer la lecture

```http
POST /api/reading-exercises/1/evaluate
```

Body:

```json
{
    "transcript": "The children visited beautiful museum yesterday."
}
```

Reponse:

```json
{
    "success": true,
    "message": "Lecture evaluee.",
    "data": {
        "exercise": {
            "id": 1,
            "title": "Museum visit",
            "text": "The children visited the beautiful museum yesterday.",
            "language": "en-US",
            "level": "beginner"
        },
        "result": {
            "score": 86,
            "status": "good",
            "is_correct": false,
            "transcript": "The children visited beautiful museum yesterday.",
            "words": [
                {
                    "expected": "The",
                    "actual": "The",
                    "status": "correct"
                },
                {
                    "expected": "children",
                    "actual": "children",
                    "status": "correct"
                },
                {
                    "expected": "the",
                    "actual": null,
                    "status": "missing"
                }
            ],
            "feedback": {
                "title": "Good job!",
                "message": "You read most of the sentence correctly. Try again and focus on the highlighted words."
            }
        },
        "attempt": {
            "id": 12,
            "score": 86,
            "status": "good",
            "is_correct": false,
            "created_at": "2026-09-03T10:00:00.000000Z"
        }
    }
}
```

Quand cette route est appelee, Laravel cree aussi une ligne dans la table:

```text
reading_exercise_attempts
```

Cette table permet de garder l historique des essais.

### Historique des tentatives

```http
GET /api/me/reading-attempts
```

Reponse:

```json
{
    "success": true,
    "message": "Tentatives de lecture recuperees.",
    "data": {
        "attempts": [
            {
                "id": 12,
                "exercise": {
                    "id": 1,
                    "title": "Museum visit",
                    "text": "The children visited the beautiful museum yesterday.",
                    "language": "en-US",
                    "level": "beginner"
                },
                "transcript": "The children visited beautiful museum yesterday.",
                "score": 86,
                "status": "good",
                "is_correct": false,
                "words": [
                    {
                        "expected": "the",
                        "actual": null,
                        "status": "missing"
                    }
                ],
                "feedback": {
                    "title": "Good job!",
                    "message": "You read most of the sentence correctly. Try again and focus on the highlighted words."
                },
                "created_at": "2026-09-03T10:00:00.000000Z"
            }
        ]
    }
}
```

### Resume de progression

```http
GET /api/me/reading-progress
```

Reponse:

```json
{
    "success": true,
    "message": "Progression de lecture recuperee.",
    "data": {
        "progress": {
            "total_attempts": 8,
            "completed_exercises": 3,
            "average_score": 82,
            "best_score": 100,
            "latest_attempt": {
                "id": 12,
                "score": 86,
                "status": "good",
                "is_correct": false,
                "created_at": "2026-09-03T10:00:00.000000Z"
            }
        }
    }
}
```

### Progression globale

```http
GET /api/me/progress
```

Reponse:

```json
{
    "success": true,
    "message": "Progression globale recuperee.",
    "data": {
        "progress": {
            "reading": {
                "learning_mode": {
                    "id": 1,
                    "name": "Reading Practice",
                    "slug": "reading"
                },
                "summary": {
                    "total_attempts": 8,
                    "completed_exercises": 3,
                    "average_score": 82,
                    "best_score": 100,
                    "latest_attempt": null
                }
            },
            "writing": {
                "learning_mode": {
                    "id": 2,
                    "name": "Writing Assistant",
                    "slug": "writing"
                },
                "summary": {
                    "total_attempts": 0,
                    "completed_exercises": 0,
                    "average_score": 0,
                    "best_score": 0,
                    "latest_attempt": null
                }
            }
        }
    }
}
```

La vraie progression est calculee pour `reading`, `writing` et `smart-abstract`.

Les modes restants retournent des scores a zero tant qu ils ne sont pas encore implementes.

## Signification des statuts

Dans `result.words`, chaque mot peut avoir un status:

- `correct`: le mot attendu a ete reconnu correctement
- `missing`: le mot attendu n a pas ete dit ou n a pas ete reconnu
- `incorrect`: le mot reconnu est different du mot attendu
- `extra`: l utilisateur a dit un mot en plus

Dans `result.status`, le score global peut etre:

- `excellent`: score superieur ou egal a 95
- `good`: score superieur ou egal a 75
- `needs_practice`: score inferieur a 75

## Comment le score est calcule

Le service `app/Services/ReadingEvaluator.php` fait 4 choses:

1. Il met le texte attendu et le transcript en minuscules.
2. Il retire la ponctuation simple.
3. Il decoupe les phrases en mots.
4. Il aligne les mots pour detecter les mots corrects, manquants, incorrects ou en trop.

Formule simplifiee:

```text
score = 100 - pourcentage d erreurs
```

Exemple:

```text
Expected:
The little boy is playing in the garden.

Transcript:
The little boy playing in garden.
```

Le backend peut detecter que `is` et `the` sont manquants.

## Fichiers importants

```text
app/Http/Controllers/Api/ReadingExerciseController.php
app/Http/Controllers/Api/LearningModeController.php
app/Http/Controllers/Api/GlobalProgressController.php
app/Http/Requests/EvaluateReadingExerciseRequest.php
app/Models/LearningMode.php
app/Models/ReadingExercise.php
app/Models/ReadingExerciseAttempt.php
app/Services/ReadingEvaluator.php
app/Services/ReadingProgressSummary.php
database/migrations/2026_09_03_020000_create_learning_modes_table.php
database/migrations/2026_09_03_020100_add_learning_mode_id_to_reading_exercises_table.php
database/migrations/2026_09_02_000000_create_reading_exercises_table.php
database/migrations/2026_09_03_010000_create_reading_exercise_attempts_table.php
database/factories/LearningModeFactory.php
database/factories/ReadingExerciseFactory.php
database/factories/ReadingExerciseAttemptFactory.php
tests/Feature/ReadingExerciseApiTest.php
tests/Feature/LearningModeApiTest.php
```

## Donnees de demo

Le seeder ajoute deux exercices:

Il ajoute aussi les learning modes:

```text
Reading Practice
Writing Assistant
Vocal Dictation
Word Splitting
Smart Abstract
```

Les exercices de lecture sont lies au mode `reading`.

```text
The children visited the beautiful museum yesterday.
The little boy is playing in the garden.
```

Pour les creer en base:

```bash
php artisan migrate --seed
```

## Packages Flutter conseilles

Cote Flutter:

```bash
flutter pub add flutter_tts speech_to_text
```

`flutter_tts` sert au bouton Listen.

`speech_to_text` sert au bouton micro.

Le backend attend seulement le transcript:

```dart
body: jsonEncode({
  'transcript': recognizedText,
});
```

## Limite du MVP

Cette version ne mesure pas parfaitement la prononciation. Elle mesure si le moteur speech-to-text a compris la phrase.

C est suffisant pour une premiere version pedagogique. Pour une vraie analyse de prononciation, il faudra plus tard utiliser un moteur specialise capable d analyser l audio ou les phonemes.
