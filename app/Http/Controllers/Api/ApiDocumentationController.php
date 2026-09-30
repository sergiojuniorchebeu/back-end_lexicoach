<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ApiDocumentationController extends Controller
{
    public function __invoke(): View
    {
        $documentation = [
            'title' => 'Documentation API',
            'version' => '1.0',
            'base_url' => url('/api'),
            'authentication_type' => 'Bearer Token avec Laravel Sanctum + roles',
            'important_headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer {access_token}',
            ],
            'roles' => [
                [
                    'name' => 'visitor',
                    'stored_in_database' => false,
                    'description' => 'Utilisateur non connecte. Il peut voir les pages publiques mais ne peut pas appeler les routes protegees.',
                    'permissions' => [
                        'Voir la landing page si elle est publique.',
                        'Creer un compte.',
                        'Se connecter.',
                    ],
                ],
                [
                    'name' => 'learner',
                    'stored_in_database' => true,
                    'description' => 'Apprenant connecte. C est le role cree automatiquement apres une inscription.',
                    'permissions' => [
                        'Acceder a son profil.',
                        'Voir les exercices de lecture.',
                        'Envoyer un transcript pour obtenir une evaluation et un score.',
                        'Faire les exercices writing avec correction IA.',
                        'Faire les exercices smart abstract avec evaluation IA.',
                        'Generer un code temporaire pour etre associe a un tutor.',
                    ],
                ],
                [
                    'name' => 'tutor',
                    'stored_in_database' => true,
                    'description' => 'Tuteur connecte. Il peut associer des learners avec un code et suivre leur progression.',
                    'permissions' => [
                        'Acceder a son profil.',
                        'Associer un learner avec un code d association.',
                        'Voir la progression des learners associes.',
                        'Voir les tentatives de lecture des learners associes.',
                        'Ne peut pas faire les exercices learner avec les routes actuelles.',
                    ],
                ],
                [
                    'name' => 'admin',
                    'stored_in_database' => true,
                    'description' => 'Administrateur connecte. Il peut consulter le dashboard admin et gerer les roles des utilisateurs.',
                    'permissions' => [
                        'Acceder a son profil.',
                        'Voir les statistiques globales.',
                        'Lister les utilisateurs.',
                        'Voir le detail d un utilisateur.',
                        'Modifier le role d un utilisateur.',
                    ],
                ],
            ],
            'modules' => [
                [
                    'name' => 'Authentification',
                    'slug' => 'authentification',
                    'description' => 'Module pour inscription, connexion, recuperation du profil connecte, roles utilisateur et deconnexion.',
                    'endpoints' => [
                        [
                            'name' => 'Inscription',
                            'method' => 'POST',
                            'path' => '/api/auth/register',
                            'protected' => false,
                            'description' => 'Cree un compte utilisateur et retourne directement un token Sanctum. Le role vaut learner par defaut. Le mobile peut envoyer role=tutor pour creer un compte tutor. Le role admin n est pas autorise sur cette route publique.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ],
                            'request_body' => [
                                'full_name' => 'Marie Dupont',
                                'email' => 'marie@example.com',
                                'password' => 'password123',
                                'password_confirmation' => 'password123',
                                'device_name' => 'flutter-app',
                                'role' => 'learner',
                            ],
                            'responses' => [
                                [
                                    'status' => 201,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Registration successful.',
                                        'data' => [
                                            'user' => [
                                                'id' => 1,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                                'email_verified_at' => null,
                                                'created_at' => '2026-08-31T10:00:00.000000Z',
                                            ],
                                            'token' => [
                                                'type' => 'Bearer',
                                                'access_token' => '1|exempleDeTokenSanctum',
                                                'expires_at' => null,
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 422,
                                    'title' => 'Erreur de validation',
                                    'body' => [
                                        'message' => 'The email has already been taken. (and 1 more error)',
                                        'errors' => [
                                            'email' => ['The email has already been taken.'],
                                            'password' => ['The password field confirmation does not match.'],
                                            'role' => ['The selected role is invalid.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Connexion',
                            'method' => 'POST',
                            'path' => '/api/auth/login',
                            'protected' => false,
                            'description' => 'Connecte un utilisateur avec email et password puis retourne un token Sanctum.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                            ],
                            'request_body' => [
                                'email' => 'marie@example.com',
                                'password' => 'password123',
                                'device_name' => 'flutter-app',
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Login successful.',
                                        'data' => [
                                            'user' => [
                                                'id' => 1,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                                'email_verified_at' => null,
                                                'created_at' => '2026-08-31T10:00:00.000000Z',
                                            ],
                                            'token' => [
                                                'type' => 'Bearer',
                                                'access_token' => '1|exempleDeTokenSanctum',
                                                'expires_at' => null,
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 401,
                                    'title' => 'Identifiants incorrects',
                                    'body' => [
                                        'message' => 'Les identifiants sont incorrects.',
                                        'errors' => [
                                            'email' => ['Les identifiants sont incorrects.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Utilisateur connecte',
                            'method' => 'GET',
                            'path' => '/api/auth/me',
                            'protected' => true,
                            'description' => 'Retourne les informations de l utilisateur connecte grace au token.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Authenticated user.',
                                        'data' => [
                                            'user' => [
                                                'id' => 1,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                                'email_verified_at' => null,
                                                'created_at' => '2026-08-31T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 401,
                                    'title' => 'Token absent ou invalide',
                                    'body' => [
                                        'message' => 'Unauthenticated.',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Deconnexion',
                            'method' => 'POST',
                            'path' => '/api/auth/logout',
                            'protected' => true,
                            'description' => 'Supprime le token actuel. Apres cette requete, ce token ne fonctionne plus.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Logout successful.',
                                        'data' => null,
                                    ],
                                ],
                                [
                                    'status' => 401,
                                    'title' => 'Token absent ou invalide',
                                    'body' => [
                                        'message' => 'Unauthenticated.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Reading exercises',
                    'slug' => 'reading-exercises',
                    'description' => 'Module pour recuperer des exercices de lecture, evaluer le texte reconnu par le micro cote Flutter et sauvegarder chaque tentative.',
                    'endpoints' => [
                        [
                            'name' => 'Liste des exercices',
                            'method' => 'GET',
                            'path' => '/api/reading-exercises',
                            'protected' => true,
                            'description' => 'Retourne les exercices actifs. Cette route est reservee au role learner. Flutter affiche ensuite le text, la langue et le niveau.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Reading exercises retrieved.',
                                        'data' => [
                                            'exercises' => [
                                                [
                                                    'id' => 1,
                                                    'title' => 'Museum visit',
                                                    'text' => 'The children visited the beautiful museum yesterday.',
                                                    'language' => 'en-US',
                                                    'level' => 'beginner',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Detail d un exercice',
                            'method' => 'GET',
                            'path' => '/api/reading-exercises/{readingExercise}',
                            'protected' => true,
                            'description' => 'Retourne un seul exercice. Cette route est reservee au role learner. Le champ language aide Flutter a choisir la voix TTS et la langue STT.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Reading exercise retrieved.',
                                        'data' => [
                                            'exercise' => [
                                                'id' => 1,
                                                'title' => 'Museum visit',
                                                'text' => 'The children visited the beautiful museum yesterday.',
                                                'language' => 'en-US',
                                                'level' => 'beginner',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 404,
                                    'title' => 'Exercice absent ou inactif',
                                    'body' => [
                                        'message' => 'Not Found',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Evaluer une lecture',
                            'method' => 'POST',
                            'path' => '/api/reading-exercises/{readingExercise}/evaluate',
                            'protected' => true,
                            'description' => 'Compare le transcript envoye par Flutter avec le text officiel stocke en base, sauvegarde une tentative et retourne le score. Cette route est reservee au role learner.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'transcript' => 'The children visited beautiful museum yesterday.',
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Reading evaluated.',
                                        'data' => [
                                            'exercise' => [
                                                'id' => 1,
                                                'title' => 'Museum visit',
                                                'text' => 'The children visited the beautiful museum yesterday.',
                                                'language' => 'en-US',
                                                'level' => 'beginner',
                                            ],
                                            'result' => [
                                                'score' => 86,
                                                'status' => 'good',
                                                'is_correct' => false,
                                                'transcript' => 'The children visited beautiful museum yesterday.',
                                                'words' => [
                                                    [
                                                        'expected' => 'The',
                                                        'actual' => 'The',
                                                        'status' => 'correct',
                                                    ],
                                                    [
                                                        'expected' => 'children',
                                                        'actual' => 'children',
                                                        'status' => 'correct',
                                                    ],
                                                    [
                                                        'expected' => 'visited',
                                                        'actual' => 'visited',
                                                        'status' => 'correct',
                                                    ],
                                                    [
                                                        'expected' => 'the',
                                                        'actual' => null,
                                                        'status' => 'missing',
                                                    ],
                                                ],
                                                'feedback' => [
                                                    'title' => 'Good job!',
                                                    'message' => 'You read most of the sentence correctly. Try again and focus on the highlighted words.',
                                                ],
                                            ],
                                            'attempt' => [
                                                'id' => 12,
                                                'score' => 86,
                                                'status' => 'good',
                                                'is_correct' => false,
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 422,
                                    'title' => 'Transcript manquant',
                                    'body' => [
                                        'message' => 'The transcript field is required.',
                                        'errors' => [
                                            'transcript' => ['The transcript field is required.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Learning modes',
                    'slug' => 'learning-modes',
                    'description' => 'Module pour lister les modes disponibles dans l application et charger les exercices lies a reading, writing ou smart abstract.',
                    'endpoints' => [
                        [
                            'name' => 'Liste des modes',
                            'method' => 'GET',
                            'path' => '/api/learning-modes',
                            'protected' => true,
                            'description' => 'Retourne les modes actifs. Flutter peut utiliser cette route pour afficher les cartes Practice Exercises.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learning modes retrieved.',
                                        'data' => [
                                            'learning_modes' => [
                                                [
                                                    'id' => 1,
                                                    'name' => 'Reading Practice',
                                                    'slug' => 'reading',
                                                    'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
                                                    'is_active' => true,
                                                    'sort_order' => 1,
                                                ],
                                                [
                                                    'id' => 2,
                                                    'name' => 'Writing Assistant',
                                                    'slug' => 'writing',
                                                    'description' => 'Write a text and receive spelling, grammar and clarity feedback.',
                                                    'is_active' => true,
                                                    'sort_order' => 2,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Detail d un mode',
                            'method' => 'GET',
                            'path' => '/api/learning-modes/{slug}',
                            'protected' => true,
                            'description' => 'Retourne un mode precis avec son slug, par exemple reading.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learning mode retrieved.',
                                        'data' => [
                                            'learning_mode' => [
                                                'id' => 1,
                                                'name' => 'Reading Practice',
                                                'slug' => 'reading',
                                                'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
                                                'is_active' => true,
                                                'sort_order' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Exercices d un mode',
                            'method' => 'GET',
                            'path' => '/api/learning-modes/{slug}/exercises',
                            'protected' => true,
                            'description' => 'Retourne les exercices actifs lies au learning mode. Les slugs actuellement operationnels sont reading, writing et smart-abstract.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learning mode exercises retrieved.',
                                        'data' => [
                                            'learning_mode' => [
                                                'id' => 1,
                                                'name' => 'Reading Practice',
                                                'slug' => 'reading',
                                                'description' => 'Read a sentence aloud, compare your transcript and improve fluency.',
                                                'is_active' => true,
                                                'sort_order' => 1,
                                            ],
                                            'exercises' => [
                                                [
                                                    'id' => 1,
                                                    'title' => 'Museum visit',
                                                    'text' => 'The children visited the beautiful museum yesterday.',
                                                    'language' => 'en-US',
                                                    'level' => 'beginner',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Writing Assistant avec IA',
                    'slug' => 'writing-assistant',
                    'description' => 'Module learner pour recuperer des sujets writing, envoyer la reponse de l apprenante, appeler Gemini cote backend, sauvegarder la tentative et retourner score plus feedback.',
                    'endpoints' => [
                        [
                            'name' => 'Liste des exercices writing',
                            'method' => 'GET',
                            'path' => '/api/writing-exercises',
                            'protected' => true,
                            'description' => 'Retourne les exercices writing actifs. Flutter affiche prompt, instructions, langue, niveau et nombre minimum de mots.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Writing exercises retrieved.',
                                        'data' => [
                                            'exercises' => [
                                                [
                                                    'id' => 1,
                                                    'title' => 'My school day',
                                                    'prompt' => 'Write five sentences about your school day.',
                                                    'instructions' => 'Use simple sentences.',
                                                    'language' => 'en-US',
                                                    'level' => 'beginner',
                                                    'min_words' => 25,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Evaluer une reponse writing',
                            'method' => 'POST',
                            'path' => '/api/writing-exercises/{writingExercise}/evaluate',
                            'protected' => true,
                            'description' => 'Envoie la reponse de l apprenante a l IA via Laravel. Le backend sauvegarde la tentative et retourne une correction exploitable par Flutter.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'answer' => 'Today I went to school and learned a new English word with my teacher.',
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Writing text evaluated.',
                                        'data' => [
                                            'result' => [
                                                'score' => 82,
                                                'status' => 'good',
                                                'answer' => 'Today I went to school...',
                                                'corrected_text' => 'Today I went to school...',
                                                'mistakes' => [],
                                                'suggestions' => ['Add one clear example.'],
                                                'feedback' => [
                                                    'title' => 'Good work!',
                                                    'message' => 'Your answer is understandable. Improve details and punctuation.',
                                                ],
                                            ],
                                            'attempt' => [
                                                'id' => 15,
                                                'score' => 82,
                                                'status' => 'good',
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 503,
                                    'title' => 'Service IA indisponible',
                                    'body' => [
                                        'success' => false,
                                        'message' => 'AI service unavailable.',
                                        'errors' => [
                                            'ai' => ['GEMINI_API_KEY is missing.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Progression writing',
                            'method' => 'GET',
                            'path' => '/api/me/writing-progress',
                            'protected' => true,
                            'description' => 'Retourne total_attempts, completed_exercises, average_score, best_score et latest_attempt pour writing.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Writing progress retrieved.',
                                        'data' => [
                                            'progress' => [
                                                'total_attempts' => 3,
                                                'completed_exercises' => 2,
                                                'average_score' => 78,
                                                'best_score' => 91,
                                                'latest_attempt' => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Smart Abstract avec IA',
                    'slug' => 'smart-abstract',
                    'description' => 'Module learner pour recuperer un texte source, envoyer un resume, appeler Gemini cote backend, sauvegarder la tentative et retourner un feedback structure.',
                    'endpoints' => [
                        [
                            'name' => 'Liste des exercices smart abstract',
                            'method' => 'GET',
                            'path' => '/api/smart-abstract-exercises',
                            'protected' => true,
                            'description' => 'Retourne les textes a resumer avec instructions, min_words et max_words.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Smart abstract exercises retrieved.',
                                        'data' => [
                                            'exercises' => [
                                                [
                                                    'id' => 1,
                                                    'title' => 'Library paragraph',
                                                    'source_text' => 'Every Wednesday, the class visits the library.',
                                                    'instructions' => 'Write a short summary with the main idea.',
                                                    'language' => 'en-US',
                                                    'level' => 'beginner',
                                                    'min_words' => 20,
                                                    'max_words' => 60,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Evaluer un resume',
                            'method' => 'POST',
                            'path' => '/api/smart-abstract-exercises/{smartAbstractExercise}/evaluate',
                            'protected' => true,
                            'description' => 'Envoie le resume de l apprenante a l IA via Laravel. Le backend compare avec le texte source, sauvegarde la tentative et retourne score, idees manquantes et feedback.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'summary' => 'The class visits the library and the teacher helps learners choose books.',
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Smart abstract evaluated.',
                                        'data' => [
                                            'result' => [
                                                'score' => 84,
                                                'status' => 'good',
                                                'summary' => 'The class visits the library...',
                                                'improved_summary' => 'The class visits the library...',
                                                'missing_ideas' => [],
                                                'strengths' => ['The summary is readable.'],
                                                'feedback' => [
                                                    'title' => 'Good summary!',
                                                    'message' => 'The summary keeps the main idea. Make it even more precise.',
                                                ],
                                            ],
                                            'attempt' => [
                                                'id' => 16,
                                                'score' => 84,
                                                'status' => 'good',
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Progression smart abstract',
                            'method' => 'GET',
                            'path' => '/api/me/smart-abstract-progress',
                            'protected' => true,
                            'description' => 'Retourne total_attempts, completed_exercises, average_score, best_score et latest_attempt pour smart abstract.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Smart abstract progress retrieved.',
                                        'data' => [
                                            'progress' => [
                                                'total_attempts' => 2,
                                                'completed_exercises' => 1,
                                                'average_score' => 81,
                                                'best_score' => 90,
                                                'latest_attempt' => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Reading progress',
                    'slug' => 'reading-progress',
                    'description' => 'Module pour recuperer l historique des tentatives de lecture et un resume de progression du learner connecte.',
                    'endpoints' => [
                        [
                            'name' => 'Historique des tentatives',
                            'method' => 'GET',
                            'path' => '/api/me/reading-attempts',
                            'protected' => true,
                            'description' => 'Retourne les tentatives de lecture du learner connecte, de la plus recente a la plus ancienne.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Reading attempts retrieved.',
                                        'data' => [
                                            'attempts' => [
                                                [
                                                    'id' => 12,
                                                    'exercise' => [
                                                        'id' => 1,
                                                        'title' => 'Museum visit',
                                                        'text' => 'The children visited the beautiful museum yesterday.',
                                                        'language' => 'en-US',
                                                        'level' => 'beginner',
                                                    ],
                                                    'transcript' => 'The children visited beautiful museum yesterday.',
                                                    'score' => 86,
                                                    'status' => 'good',
                                                    'is_correct' => false,
                                                    'words' => [
                                                        [
                                                            'expected' => 'the',
                                                            'actual' => null,
                                                            'status' => 'missing',
                                                        ],
                                                    ],
                                                    'feedback' => [
                                                        'title' => 'Good job!',
                                                        'message' => 'You read most of the sentence correctly. Try again and focus on the highlighted words.',
                                                    ],
                                                    'created_at' => '2026-09-03T10:00:00.000000Z',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Resume de progression',
                            'method' => 'GET',
                            'path' => '/api/me/reading-progress',
                            'protected' => true,
                            'description' => 'Retourne les statistiques de lecture du learner connecte: tentatives, moyenne, meilleur score et derniere tentative.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Reading progress retrieved.',
                                        'data' => [
                                            'progress' => [
                                                'total_attempts' => 8,
                                                'completed_exercises' => 3,
                                                'average_score' => 82,
                                                'best_score' => 100,
                                                'latest_attempt' => [
                                                    'id' => 12,
                                                    'score' => 86,
                                                    'status' => 'good',
                                                    'is_correct' => false,
                                                    'created_at' => '2026-09-03T10:00:00.000000Z',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Global progress',
                    'slug' => 'global-progress',
                    'description' => 'Module pour retourner une progression globale organisee par learning mode.',
                    'endpoints' => [
                        [
                            'name' => 'Progression globale',
                            'method' => 'GET',
                            'path' => '/api/me/progress',
                            'protected' => true,
                            'description' => 'Retourne la progression du learner connecte pour chaque mode actif. Reading, writing et smart abstract ont de vraies statistiques; les autres modes restent a zero.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Global progress retrieved.',
                                        'data' => [
                                            'progress' => [
                                                'reading' => [
                                                    'learning_mode' => [
                                                        'id' => 1,
                                                        'name' => 'Reading Practice',
                                                        'slug' => 'reading',
                                                    ],
                                                    'summary' => [
                                                        'total_attempts' => 8,
                                                        'completed_exercises' => 3,
                                                        'average_score' => 82,
                                                        'best_score' => 100,
                                                        'latest_attempt' => null,
                                                    ],
                                                ],
                                                'writing' => [
                                                    'learning_mode' => [
                                                        'id' => 2,
                                                        'name' => 'Writing Assistant',
                                                        'slug' => 'writing',
                                                    ],
                                                    'summary' => [
                                                        'total_attempts' => 0,
                                                        'completed_exercises' => 0,
                                                        'average_score' => 0,
                                                        'best_score' => 0,
                                                        'latest_attempt' => null,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Learner association code',
                    'slug' => 'learner-association-code',
                    'description' => 'Module qui permet a un learner de generer un code temporaire pour etre associe a un tutor.',
                    'endpoints' => [
                        [
                            'name' => 'Voir le code actif',
                            'method' => 'GET',
                            'path' => '/api/me/association-code',
                            'protected' => true,
                            'description' => 'Retourne le code actif du learner connecte, ou null s il n existe pas de code actif.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Code actif',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Code d association actif recupere.',
                                        'data' => [
                                            'association_code' => [
                                                'id' => 3,
                                                'code' => 'LC-123456',
                                                'expires_at' => '2026-09-04T10:00:00.000000Z',
                                                'used_at' => null,
                                                'cancelled_at' => null,
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 200,
                                    'title' => 'Aucun code actif',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Aucun code d association actif.',
                                        'data' => [
                                            'association_code' => null,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Generer un code',
                            'method' => 'POST',
                            'path' => '/api/me/association-code',
                            'protected' => true,
                            'description' => 'Genere un code valable 24h pour associer le learner connecte a un tutor. Si un code actif existe deja, il est retourne.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 201,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Association code generated.',
                                        'data' => [
                                            'association_code' => [
                                                'id' => 3,
                                                'code' => 'LC-123456',
                                                'expires_at' => '2026-09-04T10:00:00.000000Z',
                                                'used_at' => null,
                                                'cancelled_at' => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Regenerer un code',
                            'method' => 'POST',
                            'path' => '/api/me/association-code/regenerate',
                            'protected' => true,
                            'description' => 'Annule le code actif du learner, puis cree un nouveau code valable 24h.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 201,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Association code regenerated.',
                                        'data' => [
                                            'association_code' => [
                                                'id' => 4,
                                                'code' => 'LC-654321',
                                                'expires_at' => '2026-09-04T10:00:00.000000Z',
                                                'used_at' => null,
                                                'cancelled_at' => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Annuler le code actif',
                            'method' => 'DELETE',
                            'path' => '/api/me/association-code',
                            'protected' => true,
                            'description' => 'Annule le code actif du learner connecte. Apres annulation, le tutor ne peut plus utiliser ce code.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Code d association annule.',
                                        'data' => [
                                            'association_code' => [
                                                'id' => 3,
                                                'code' => 'LC-123456',
                                                'expires_at' => '2026-09-04T10:00:00.000000Z',
                                                'used_at' => null,
                                                'cancelled_at' => '2026-09-03T10:30:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Tutor learners',
                    'slug' => 'tutor-learners',
                    'description' => 'Module qui permet a un tutor d associer des learners avec un code, puis de suivre leur progression.',
                    'endpoints' => [
                        [
                            'name' => 'Dashboard tutor',
                            'method' => 'GET',
                            'path' => '/api/tutor/dashboard',
                            'protected' => true,
                            'description' => 'Retourne un resume global des learners associes au tutor: nombre de learners, moyennes reading/writing/smart abstract, dernieres tentatives et learners a surveiller.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Tutor dashboard retrieved.',
                                        'data' => [
                                            'dashboard' => [
                                                'total_learners' => 4,
                                                'active_learners' => 3,
                                                'reading' => [
                                                    'total_attempts' => 18,
                                                    'average_score' => 76,
                                                    'best_score' => 100,
                                                    'latest_attempts' => [],
                                                    'learners_needing_attention' => [],
                                                ],
                                                'progress_by_mode' => [
                                                    'reading' => [
                                                        'learning_mode' => [
                                                            'id' => 1,
                                                            'name' => 'Reading Practice',
                                                            'slug' => 'reading',
                                                        ],
                                                        'summary' => [
                                                            'total_attempts' => 18,
                                                            'average_score' => 76,
                                                            'best_score' => 100,
                                                            'latest_attempts' => [],
                                                        ],
                                                    ],
                                                    'writing' => [
                                                        'learning_mode' => [
                                                            'id' => 2,
                                                            'name' => 'Writing Assistant',
                                                            'slug' => 'writing',
                                                        ],
                                                        'summary' => [
                                                            'total_attempts' => 0,
                                                            'average_score' => 0,
                                                            'best_score' => 0,
                                                            'latest_attempts' => [],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Associer un learner',
                            'method' => 'POST',
                            'path' => '/api/tutor/learners/link',
                            'protected' => true,
                            'description' => 'Associe le tutor connecte au learner proprietaire du code. Le code doit etre valide, non expire et non utilise.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'code' => 'LC-123456',
                            ],
                            'responses' => [
                                [
                                    'status' => 201,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learner linked to tutor.',
                                        'data' => [
                                            'learner' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 422,
                                    'title' => 'Code invalide',
                                    'body' => [
                                        'message' => 'Ce code d association est invalide ou expire.',
                                        'errors' => [
                                            'code' => ['Ce code d association est invalide ou expire.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Liste des learners associes',
                            'method' => 'GET',
                            'path' => '/api/tutor/learners',
                            'protected' => true,
                            'description' => 'Retourne uniquement les learners associes au tutor connecte.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Tutor learners retrieved.',
                                        'data' => [
                                            'learners' => [
                                                [
                                                    'id' => 2,
                                                    'full_name' => 'Marie Dupont',
                                                    'email' => 'marie@example.com',
                                                    'role' => 'learner',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Progression d un learner',
                            'method' => 'GET',
                            'path' => '/api/tutor/learners/{learner}/progress',
                            'protected' => true,
                            'description' => 'Retourne la progression par learning mode d un learner associe au tutor connecte. Reading, writing et smart abstract contiennent les vrais scores.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learner progress retrieved.',
                                        'data' => [
                                            'learner' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                            ],
                                            'progress' => [
                                                'reading' => [
                                                    'learning_mode' => [
                                                        'id' => 1,
                                                        'name' => 'Reading Practice',
                                                        'slug' => 'reading',
                                                    ],
                                                    'summary' => [
                                                        'total_attempts' => 8,
                                                        'completed_exercises' => 3,
                                                        'average_score' => 82,
                                                        'best_score' => 100,
                                                        'latest_attempt' => null,
                                                    ],
                                                ],
                                                'writing' => [
                                                    'learning_mode' => [
                                                        'id' => 2,
                                                        'name' => 'Writing Assistant',
                                                        'slug' => 'writing',
                                                    ],
                                                    'summary' => [
                                                        'total_attempts' => 0,
                                                        'completed_exercises' => 0,
                                                        'average_score' => 0,
                                                        'best_score' => 0,
                                                        'latest_attempt' => null,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'status' => 403,
                                    'title' => 'Learner non associe',
                                    'body' => [
                                        'message' => 'This learner is not linked to this tutor.',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Tentatives de lecture d un learner',
                            'method' => 'GET',
                            'path' => '/api/tutor/learners/{learner}/reading-attempts',
                            'protected' => true,
                            'description' => 'Retourne l historique reading d un learner associe au tutor connecte.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learner reading attempts retrieved.',
                                        'data' => [
                                            'learner' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                            ],
                                            'attempts' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Detacher un learner',
                            'method' => 'DELETE',
                            'path' => '/api/tutor/learners/{learner}',
                            'protected' => true,
                            'description' => 'Supprime le lien entre le tutor connecte et le learner. Les tentatives du learner ne sont pas supprimees.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Learner detached from tutor.',
                                        'data' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Admin dashboard',
                    'slug' => 'admin-dashboard',
                    'description' => 'Module reserve au role admin pour consulter les statistiques globales et gerer les roles utilisateur.',
                    'endpoints' => [
                        [
                            'name' => 'Dashboard admin',
                            'method' => 'GET',
                            'path' => '/api/admin/dashboard',
                            'protected' => true,
                            'description' => 'Retourne les statistiques globales: utilisateurs par role, activite reading, associations tutor/learner et dernieres tentatives.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Admin dashboard retrieved.',
                                        'data' => [
                                            'dashboard' => [
                                                'users' => [
                                                    'total' => 25,
                                                    'learners' => 18,
                                                    'tutors' => 5,
                                                    'admins' => 2,
                                                    'recent' => [],
                                                ],
                                                'learning' => [
                                                    'modes' => 5,
                                                    'reading_exercises' => 12,
                                                    'reading_attempts' => 80,
                                                    'average_reading_score' => 74,
                                                    'best_reading_score' => 100,
                                                    'writing_exercises' => 6,
                                                    'writing_attempts' => 32,
                                                    'average_writing_score' => 78,
                                                    'smart_abstract_exercises' => 5,
                                                    'smart_abstract_attempts' => 18,
                                                    'average_smart_abstract_score' => 81,
                                                ],
                                                'tutor_view' => [
                                                    'linked_pairs' => 10,
                                                    'tutors_with_learners' => 4,
                                                ],
                                                'recent_attempts' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Lister les utilisateurs',
                            'method' => 'GET',
                            'path' => '/api/admin/users',
                            'protected' => true,
                            'description' => 'Retourne les 100 comptes les plus recents. La route accepte les filtres optionnels role et search.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'Users retrieved.',
                                        'data' => [
                                            'users' => [
                                                [
                                                    'id' => 2,
                                                    'full_name' => 'Marie Dupont',
                                                    'email' => 'marie@example.com',
                                                    'role' => 'learner',
                                                    'reading_attempts_count' => 4,
                                                    'learners_count' => 0,
                                                    'tutors_count' => 1,
                                                    'created_at' => '2026-09-03T10:00:00.000000Z',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Voir un utilisateur',
                            'method' => 'GET',
                            'path' => '/api/admin/users/{user}',
                            'protected' => true,
                            'description' => 'Retourne le detail d un compte utilisateur.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => null,
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'User retrieved.',
                                        'data' => [
                                            'user' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                                'conversation_limits' => [
                                                    'session_limit_seconds' => 180,
                                                    'session_limit_minutes' => 3,
                                                    'daily_session_limit' => 3,
                                                ],
                                                'reading_attempts_count' => 4,
                                                'learners_count' => 0,
                                                'tutors_count' => 1,
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Modifier le role',
                            'method' => 'PATCH',
                            'path' => '/api/admin/users/{user}/role',
                            'protected' => true,
                            'description' => 'Permet a un admin de changer le role d un utilisateur. Un admin ne peut pas retirer son propre role admin.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'role' => 'tutor',
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'User role updated.',
                                        'data' => [
                                            'user' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'tutor',
                                                'conversation_limits' => [
                                                    'session_limit_seconds' => 180,
                                                    'session_limit_minutes' => 3,
                                                    'daily_session_limit' => 3,
                                                ],
                                                'reading_attempts_count' => 4,
                                                'learners_count' => 0,
                                                'tutors_count' => 1,
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Modifier les limites conversation IA',
                            'method' => 'PATCH',
                            'path' => '/api/admin/users/{user}/conversation-limits',
                            'protected' => true,
                            'description' => 'Permet a un admin de modifier la duree maximale d une session conversation IA et le nombre de sessions autorisees par jour pour un utilisateur.',
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Authorization' => 'Bearer 1|exempleDeTokenSanctum',
                            ],
                            'request_body' => [
                                'ai_conversation_session_limit_seconds' => 180,
                                'ai_conversation_daily_session_limit' => 3,
                            ],
                            'responses' => [
                                [
                                    'status' => 200,
                                    'title' => 'Succes',
                                    'body' => [
                                        'success' => true,
                                        'message' => 'AI conversation limits updated.',
                                        'data' => [
                                            'user' => [
                                                'id' => 2,
                                                'full_name' => 'Marie Dupont',
                                                'email' => 'marie@example.com',
                                                'role' => 'learner',
                                                'conversation_limits' => [
                                                    'session_limit_seconds' => 180,
                                                    'session_limit_minutes' => 3,
                                                    'daily_session_limit' => 3,
                                                ],
                                                'reading_attempts_count' => 4,
                                                'learners_count' => 0,
                                                'tutors_count' => 1,
                                                'created_at' => '2026-09-03T10:00:00.000000Z',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'frontend_steps' => [
                'Appeler POST /api/auth/register ou POST /api/auth/login.',
                'Recuperer data.token.access_token dans la reponse.',
                'Lire data.user.role pour savoir si l utilisateur est learner, tutor ou admin.',
                'Sauvegarder le token cote frontend de maniere securisee.',
                'Envoyer Authorization: Bearer {access_token} pour les routes protegees.',
                'Ne montrer les ecrans Reading Practice qu aux utilisateurs avec role learner.',
                'Appeler GET /api/learning-modes pour afficher les modes depuis le backend.',
                'Quand le mode reading est choisi, appeler GET /api/learning-modes/reading/exercises.',
                'Quand le mode writing est choisi, appeler GET /api/learning-modes/writing/exercises.',
                'Quand le mode smart abstract est choisi, appeler GET /api/learning-modes/smart-abstract/exercises.',
                'Pour Reading Practice, utiliser flutter_tts pour lire le text et speech_to_text pour obtenir le transcript.',
                'Envoyer seulement le transcript au backend. Le backend compare avec le text officiel de l exercice et sauvegarde la tentative.',
                'Pour Writing Assistant, envoyer seulement answer au backend avec POST /api/writing-exercises/{id}/evaluate.',
                'Pour Smart Abstract, envoyer seulement summary au backend avec POST /api/smart-abstract-exercises/{id}/evaluate.',
                'Ne jamais appeler Gemini directement depuis Flutter. Le backend garde la cle API et parle a Gemini.',
                'Appeler GET /api/me/reading-progress pour afficher la progression du learner.',
                'Appeler GET /api/me/writing-progress et GET /api/me/smart-abstract-progress pour les nouveaux modes IA.',
                'Pour associer un tutor, le learner genere un code avec POST /api/me/association-code.',
                'Le learner peut annuler son code avec DELETE /api/me/association-code ou le regenerer avec POST /api/me/association-code/regenerate.',
                'Le tutor saisit ce code et appelle POST /api/tutor/learners/link.',
                'Le tutor charge son dashboard avec GET /api/tutor/dashboard.',
                'Le tutor charge ensuite ses learners avec GET /api/tutor/learners.',
                'Le tutor consulte la progression avec GET /api/tutor/learners/{learner}/progress et les tentatives avec GET /api/tutor/learners/{learner}/reading-attempts.',
                'Le tutor peut aussi consulter GET /api/tutor/learners/{learner}/writing-attempts et GET /api/tutor/learners/{learner}/smart-abstract-attempts.',
                'Le tutor peut detacher un learner avec DELETE /api/tutor/learners/{learner}.',
                'Pour l admin web, ouvrir /admin/login, se connecter avec un compte admin, puis la page redirige vers /admin/dashboard.',
                'L admin peut changer un role avec PATCH /api/admin/users/{user}/role.',
                'L admin peut modifier les limites conversation IA avec PATCH /api/admin/users/{user}/conversation-limits.',
                'Appeler POST /api/auth/logout pour supprimer le token actuel.',
            ],
            'feature_guides' => [
                [
                    'title' => 'Scenario 1 - Inscription',
                    'goal' => 'Permettre a une nouvelle utilisatrice de creer son compte learner et d entrer dans l application.',
                    'user_story' => 'L utilisatrice remplit son nom, son email, son mot de passe et confirme son mot de passe. Elle appuie sur Sign up.',
                    'frontend_tasks' => [
                        'Verifier que les champs ne sont pas vides avant d appeler l API.',
                        'Verifier que password et password_confirmation sont identiques.',
                        'Afficher un loader pendant l appel API pour eviter les doubles clics.',
                        'Sauvegarder data.token.access_token si l inscription reussit.',
                        'Sauvegarder ou lire data.user.role pour connaitre le role de l utilisatrice.',
                        'Rediriger vers le dashboard ou vers Practice Exercises apres le succes.',
                    ],
                    'api_flow' => [
                        'POST /api/auth/register',
                        'Le backend cree le User avec role learner.',
                        'Le backend retourne le user et un token Sanctum.',
                    ],
                    'display_rules' => [
                        'Si success vaut true, afficher un message de succes court.',
                        'Si Laravel retourne 422, afficher les erreurs sous les champs concernes.',
                        'Ne jamais afficher le password dans l interface ou dans les logs.',
                    ],
                ],
                [
                    'title' => 'Scenario 2 - Connexion',
                    'goal' => 'Permettre a une utilisatrice existante de recuperer son token et d acceder aux routes protegees.',
                    'user_story' => 'L utilisatrice entre son email et son mot de passe, puis appuie sur Login.',
                    'frontend_tasks' => [
                        'Envoyer email, password et device_name au backend.',
                        'Sauvegarder le token dans flutter_secure_storage.',
                        'Lire data.user.role pour savoir quel espace ouvrir.',
                        'Garder le user connecte en memoire pour afficher son nom dans l app.',
                        'Ajouter Authorization: Bearer {token} sur toutes les routes protegees.',
                    ],
                    'api_flow' => [
                        'POST /api/auth/login',
                        'Le backend verifie email et password.',
                        'Le backend retourne le user et un token si les identifiants sont bons.',
                    ],
                    'display_rules' => [
                        'Si la reponse est 401, afficher que les identifiants sont incorrects.',
                        'Si la reponse est 422, afficher les erreurs de validation.',
                        'Ne pas rediriger tant que le token n est pas sauvegarde.',
                        'Si role vaut learner, ouvrir les ecrans d exercices. Si role vaut tutor, ouvrir l espace de suivi des learners. Si role vaut admin, ouvrir un espace adapte plus tard.',
                    ],
                ],
                [
                    'title' => 'Scenario 3 - Choisir un learning mode',
                    'goal' => 'Afficher les modes disponibles depuis le backend au lieu de les coder en dur dans Flutter.',
                    'user_story' => 'L utilisatrice ouvre Practice Exercises et voit les modes Reading, Writing, Dictation, Word Splitting et Smart Abstract.',
                    'frontend_tasks' => [
                        'Verifier que le token existe avant de charger la page.',
                        'Verifier que le user connecte a le role learner.',
                        'Appeler GET /api/learning-modes.',
                        'Afficher les cartes avec name et description.',
                        'Utiliser slug pour savoir quelle page ouvrir quand l utilisatrice clique.',
                    ],
                    'api_flow' => [
                        'GET /api/learning-modes',
                        'Optionnel: GET /api/learning-modes/{slug} pour recuperer un mode precis.',
                    ],
                    'display_rules' => [
                        'Afficher un loader pendant le chargement.',
                        'Afficher seulement les modes retournes par le backend.',
                        'Si la reponse est 401, renvoyer vers l ecran de login.',
                        'Si la reponse est 403, afficher que ce compte n a pas acces aux exercices learner.',
                    ],
                ],
                [
                    'title' => 'Scenario 4 - Ouvrir Reading Practice',
                    'goal' => 'Afficher a l utilisatrice une phrase officielle a lire apres le choix du mode reading.',
                    'user_story' => 'L utilisatrice clique sur le mode Reading, puis voit une phrase a lire.',
                    'frontend_tasks' => [
                        'Appeler GET /api/learning-modes/reading/exercises.',
                        'Afficher le premier exercice ou laisser l utilisatrice en choisir un.',
                        'Garder exercise.id, exercise.text et exercise.language dans l etat de la page.',
                    ],
                    'api_flow' => [
                        'GET /api/learning-modes/reading/exercises',
                        'Optionnel: GET /api/reading-exercises/{readingExercise} pour recharger un exercice precis.',
                    ],
                    'display_rules' => [
                        'Afficher le texte de exercise.text exactement comme le backend le retourne.',
                        'Afficher un etat vide si aucun exercice n est disponible.',
                    ],
                ],
                [
                    'title' => 'Scenario 5 - Bouton Listen avec TTS',
                    'goal' => 'Permettre a l utilisatrice d ecouter la bonne lecture avant de parler.',
                    'user_story' => 'L utilisatrice appuie sur Listen. Le telephone lit la phrase avec une voix claire.',
                    'frontend_tasks' => [
                        'Utiliser le package flutter_tts cote Flutter.',
                        'Utiliser exercise.language pour choisir la langue, par exemple en-US.',
                        'Lire exercise.text avec flutterTts.speak(exercise.text).',
                        'Ajouter une option Slow si besoin avec une vitesse plus basse.',
                    ],
                    'api_flow' => [
                        'Aucun appel backend n est necessaire pour le TTS dans le MVP.',
                        'Le backend fournit seulement le texte et la langue de l exercice.',
                    ],
                    'display_rules' => [
                        'Desactiver temporairement le bouton Listen pendant la lecture audio.',
                        'Prevoir un bouton Stop si la lecture est longue.',
                        'Ne pas envoyer le texte au backend pour le faire lire dans cette version.',
                    ],
                ],
                [
                    'title' => 'Scenario 6 - Bouton Micro avec STT',
                    'goal' => 'Transformer ce que l utilisatrice dit en texte reconnu.',
                    'user_story' => 'L utilisatrice appuie sur le micro, lit la phrase a voix haute, puis l app affiche ce qu elle a dit.',
                    'frontend_tasks' => [
                        'Demander la permission microphone avant de commencer.',
                        'Utiliser le package speech_to_text cote Flutter.',
                        'Utiliser exercise.language comme locale de reconnaissance vocale.',
                        'Afficher le transcript en direct si le package le permet.',
                        'Garder le dernier transcript reconnu dans l etat de la page.',
                    ],
                    'api_flow' => [
                        'Aucun audio n est envoye au backend dans le MVP.',
                        'Le backend recevra seulement le transcript final.',
                    ],
                    'display_rules' => [
                        'Afficher Listening pendant que le micro ecoute.',
                        'Afficher un message clair si la permission micro est refusee.',
                        'Ne pas appeler evaluate tant que transcript est vide.',
                    ],
                ],
                [
                    'title' => 'Scenario 7 - Evaluation de la lecture',
                    'goal' => 'Comparer le transcript avec le texte officiel et afficher un feedback utile.',
                    'user_story' => 'Apres avoir parle, l utilisatrice appuie sur Check ou l app lance l evaluation automatiquement.',
                    'frontend_tasks' => [
                        'Envoyer seulement transcript au backend.',
                        'Ne pas envoyer expected_text depuis Flutter, car le backend est la source de verite.',
                        'Lire data.result.score pour afficher le pourcentage.',
                        'Lire data.result.words pour colorer les mots corrects, manquants, incorrects ou en trop.',
                        'Lire data.result.feedback.title et message pour afficher le feedback.',
                        'Lire data.attempt.id si le frontend veut garder la reference de la tentative sauvegardee.',
                    ],
                    'api_flow' => [
                        'POST /api/reading-exercises/{readingExercise}/evaluate',
                        'Le backend recupere exercise.text en base.',
                        'Le backend compare exercise.text avec transcript.',
                        'Le backend sauvegarde une ligne dans reading_exercise_attempts.',
                        'Le backend retourne score, status, is_correct, words, feedback et attempt.',
                    ],
                    'display_rules' => [
                        'Si is_correct vaut true, afficher une felicitation.',
                        'Si status vaut good, encourager a reessayer les mots surlignes.',
                        'Si status vaut needs_practice, proposer Listen puis Try again.',
                        'Si la reponse est 422, verifier que transcript n est pas vide.',
                    ],
                ],
                [
                    'title' => 'Scenario 8 - Voir la progression globale',
                    'goal' => 'Afficher un resume simple de la progression du learner connecte.',
                    'user_story' => 'L utilisatrice ouvre son dashboard ou son profil et voit ses scores de lecture.',
                    'frontend_tasks' => [
                        'Appeler GET /api/me/progress avec le Bearer token pour le dashboard global.',
                        'Lire data.progress.reading.summary.total_attempts pour les essais de lecture.',
                        'Lire data.progress.reading.summary.average_score pour la moyenne de lecture.',
                        'Afficher reading, writing et smart abstract avec leurs vraies statistiques, puis les autres modes avec des statistiques a zero.',
                        'Appeler GET /api/me/reading-attempts si on veut afficher l historique complet.',
                    ],
                    'api_flow' => [
                        'GET /api/me/progress',
                        'GET /api/me/reading-progress',
                        'Le backend filtre les tentatives du user connecte.',
                        'Le backend calcule total_attempts, completed_exercises, average_score et best_score pour reading.',
                        'GET /api/me/reading-attempts retourne les tentatives detaillees.',
                    ],
                    'display_rules' => [
                        'Si total_attempts vaut 0, afficher un etat vide et proposer de commencer un exercice.',
                        'Si latest_attempt existe, afficher le dernier score et le feedback.',
                        'Ne pas afficher les tentatives d un autre utilisateur.',
                    ],
                ],
                [
                    'title' => 'Scenario 9 - Learner genere un code pour son tutor',
                    'goal' => 'Permettre au learner de partager un code temporaire avec son tutor.',
                    'user_story' => 'Le learner ouvre son profil ou une page Mon tutor, appuie sur Generer un code, puis donne ce code a son tutor.',
                    'frontend_tasks' => [
                        'Verifier que le user connecte a le role learner.',
                        'Appeler GET /api/me/association-code pour voir si un code actif existe deja.',
                        'Si aucun code actif n existe, appeler POST /api/me/association-code.',
                        'Si le learner veut changer de code, appeler POST /api/me/association-code/regenerate.',
                        'Si le learner veut annuler son code, appeler DELETE /api/me/association-code.',
                        'Afficher data.association_code.code de maniere lisible, par exemple LC-123456.',
                        'Afficher expires_at pour expliquer que le code expire apres 24h.',
                    ],
                    'api_flow' => [
                        'GET /api/me/association-code',
                        'Si association_code vaut null, POST /api/me/association-code',
                        'Optionnel: POST /api/me/association-code/regenerate pour annuler l ancien code et creer un nouveau code.',
                        'Optionnel: DELETE /api/me/association-code pour annuler le code actif.',
                        'Le backend cree un code unique lie au learner connecte.',
                        'Le backend retourne le code, expires_at, used_at et cancelled_at.',
                    ],
                    'display_rules' => [
                        'Ne pas demander au learner de saisir lui-meme son code.',
                        'Si used_at n est pas null, le code a deja ete utilise.',
                        'Si cancelled_at n est pas null, le code a ete annule.',
                        'Si la reponse est 403, ce compte n est pas un learner.',
                    ],
                ],
                [
                    'title' => 'Scenario 10 - Tutor associe un learner avec le code',
                    'goal' => 'Permettre au tutor d ajouter un learner dans son espace de suivi.',
                    'user_story' => 'Le tutor recoit un code du learner, le saisit dans son application, puis appuie sur Associer.',
                    'frontend_tasks' => [
                        'Verifier que le user connecte a le role tutor.',
                        'Afficher un champ code simple avec un exemple de format LC-123456.',
                        'Envoyer le code au backend avec POST /api/tutor/learners/link.',
                        'Ajouter le learner retourne dans la liste locale du tutor apres succes.',
                        'Recharger GET /api/tutor/learners pour etre sur que la liste est a jour.',
                    ],
                    'api_flow' => [
                        'POST /api/tutor/learners/link',
                        'Le backend cherche un code non expire et non utilise.',
                        'Le backend cree le lien dans tutor_learners.',
                        'Le backend marque le code comme utilise avec used_at.',
                    ],
                    'display_rules' => [
                        'Si Laravel retourne 422, afficher que le code est invalide, expire ou deja utilise.',
                        'Si Laravel retourne 403, ce compte n a pas le role tutor.',
                        'Ne pas afficher la progression tant que l association n a pas reussi.',
                    ],
                ],
                [
                    'title' => 'Scenario 11 - Tutor consulte la progression du learner',
                    'goal' => 'Afficher au tutor les scores et tentatives de lecture d un learner associe.',
                    'user_story' => 'Le tutor ouvre son espace, choisit un learner associe, puis voit sa progression reading, writing, smart abstract et son historique.',
                    'frontend_tasks' => [
                        'Appeler GET /api/tutor/learners pour afficher les learners associes.',
                        'Quand le tutor clique sur un learner, garder learner.id.',
                        'Appeler GET /api/tutor/learners/{learner}/progress pour afficher le resume.',
                        'Appeler GET /api/tutor/learners/{learner}/reading-attempts pour afficher les details reading.',
                        'Appeler GET /api/tutor/learners/{learner}/writing-attempts pour afficher les details writing.',
                        'Appeler GET /api/tutor/learners/{learner}/smart-abstract-attempts pour afficher les details smart abstract.',
                        'Afficher total_attempts, average_score, best_score et latest_attempt si disponible.',
                    ],
                    'api_flow' => [
                        'GET /api/tutor/learners',
                        'GET /api/tutor/learners/{learner}/progress',
                        'GET /api/tutor/learners/{learner}/reading-attempts',
                        'GET /api/tutor/learners/{learner}/writing-attempts',
                        'GET /api/tutor/learners/{learner}/smart-abstract-attempts',
                        'Le backend verifie que le learner est bien associe au tutor connecte.',
                    ],
                    'display_rules' => [
                        'Si la liste est vide, proposer au tutor d associer un learner avec un code.',
                        'Si la progression retourne 403, ne pas afficher les donnees de ce learner.',
                        'Si total_attempts vaut 0, afficher un etat vide: aucune tentative pour le moment.',
                    ],
                ],
                [
                    'title' => 'Scenario 12 - Tutor voit son dashboard global',
                    'goal' => 'Donner au tutor une vue rapide sur tous ses learners associes.',
                    'user_story' => 'Le tutor ouvre son espace et voit le nombre de learners, les moyennes par module, les dernieres tentatives et les learners a surveiller.',
                    'frontend_tasks' => [
                        'Verifier que le user connecte a le role tutor.',
                        'Appeler GET /api/tutor/dashboard au chargement de l espace tutor.',
                        'Lire data.dashboard.total_learners pour afficher le nombre d apprenants.',
                        'Lire data.dashboard.reading.average_score pour afficher la moyenne reading.',
                        'Lire data.dashboard.writing.average_score pour afficher la moyenne writing.',
                        'Lire data.dashboard.smart_abstract.average_score pour afficher la moyenne smart abstract.',
                        'Lire data.dashboard.reading.latest_attempts pour afficher les dernieres activites.',
                        'Lire data.dashboard.reading.learners_needing_attention pour afficher les learners sans tentative ou avec une moyenne faible.',
                    ],
                    'api_flow' => [
                        'GET /api/tutor/dashboard',
                        'Le backend recupere uniquement les learners associes au tutor connecte.',
                        'Le backend calcule les statistiques globales reading, writing et smart abstract.',
                        'Le backend retourne aussi progress_by_mode avec les vrais scores des modes implementes.',
                    ],
                    'display_rules' => [
                        'Si total_learners vaut 0, afficher un etat vide et proposer d associer un learner.',
                        'Si latest_attempts est vide, afficher aucune activite recente.',
                        'Ne jamais afficher les donnees des learners d un autre tutor.',
                    ],
                ],
                [
                    'title' => 'Scenario 13 - Tutor detache un learner',
                    'goal' => 'Permettre au tutor de retirer un learner de son espace de suivi.',
                    'user_story' => 'Le tutor ouvre la fiche d un learner associe et appuie sur retirer le learner.',
                    'frontend_tasks' => [
                        'Demander une confirmation avant de detacher le learner.',
                        'Appeler DELETE /api/tutor/learners/{learner}.',
                        'Retirer le learner de la liste locale apres succes.',
                        'Recharger GET /api/tutor/learners ou GET /api/tutor/dashboard pour actualiser l interface.',
                    ],
                    'api_flow' => [
                        'DELETE /api/tutor/learners/{learner}',
                        'Le backend verifie que le learner est associe au tutor connecte.',
                        'Le backend supprime seulement la relation tutor_learners.',
                        'Les comptes et les tentatives du learner restent en base.',
                    ],
                    'display_rules' => [
                        'Si la reponse est 403, le learner n appartient pas au tutor connecte.',
                        'Afficher un message simple apres succes.',
                        'Ne pas supprimer l historique du learner dans l interface, seulement le retirer de ce tutor.',
                    ],
                ],
                [
                    'title' => 'Scenario 14 - Admin utilise login et dashboard separes',
                    'goal' => 'Permettre a l admin de piloter les comptes et les statistiques depuis une page web douce et simple.',
                    'user_story' => 'L admin ouvre /admin/login, se connecte, puis arrive sur /admin/dashboard pour voir les statistiques globales, filtrer les utilisateurs et modifier les roles si necessaire.',
                    'frontend_tasks' => [
                        'Ouvrir /admin/login dans le navigateur.',
                        'Se connecter avec email et password d un compte admin sur la page login.',
                        'Sauvegarder le token admin dans le localStorage du navigateur pour cette SPA.',
                        'Rediriger vers /admin/dashboard apres connexion admin reussie.',
                        'Sur /admin/dashboard, rediriger vers /admin/login si aucun token admin n existe.',
                        'Appeler GET /api/admin/dashboard pour remplir les cartes statistiques.',
                        'Appeler GET /api/admin/users pour remplir la table utilisateurs.',
                        'Utiliser le filtre role et la recherche pour trouver un compte.',
                        'Appeler PATCH /api/admin/users/{user}/role pour changer learner, tutor ou admin.',
                        'Appeler PATCH /api/admin/users/{user}/conversation-limits pour modifier les limites conversation IA du compte.',
                    ],
                    'api_flow' => [
                        'POST /api/auth/login',
                        'GET /api/auth/me pour verifier que role vaut admin.',
                        'GET /api/admin/dashboard',
                        'GET /api/admin/users',
                        'PATCH /api/admin/users/{user}/role',
                        'PATCH /api/admin/users/{user}/conversation-limits',
                    ],
                    'display_rules' => [
                        'Si le compte connecte n est pas admin, afficher un message et ne pas charger les donnees admin.',
                        'Si un changement de role reussit, mettre a jour la ligne utilisateur et les statistiques.',
                        'Ne pas permettre a un admin de retirer son propre role admin.',
                    ],
                ],
                [
                    'title' => 'Scenario 15 - Learner utilise Writing Assistant',
                    'goal' => 'Permettre a l apprenante d ecrire un texte et de recevoir une correction IA sauvegardee.',
                    'user_story' => 'L utilisatrice choisit Writing Assistant, lit le sujet, ecrit sa reponse, puis recoit un score et des conseils.',
                    'frontend_tasks' => [
                        'Appeler GET /api/learning-modes/writing/exercises ou GET /api/writing-exercises.',
                        'Afficher title, prompt, instructions et min_words.',
                        'Ajouter un champ texte confortable pour que l apprenante ecrive sa reponse.',
                        'Bloquer le bouton Check si answer est vide ou trop court.',
                        'Appeler POST /api/writing-exercises/{writingExercise}/evaluate avec answer.',
                        'Afficher data.result.score, status, corrected_text, mistakes, suggestions et feedback.',
                        'Appeler GET /api/me/writing-progress pour actualiser les statistiques.',
                    ],
                    'api_flow' => [
                        'GET /api/writing-exercises',
                        'POST /api/writing-exercises/{writingExercise}/evaluate',
                        'Le backend recupere l exercice officiel.',
                        'Le backend appelle Gemini si AI_PROVIDER vaut gemini.',
                        'Le backend sauvegarde writing_exercise_attempts.',
                        'Le backend retourne le resultat pret pour Flutter.',
                    ],
                    'display_rules' => [
                        'Si status vaut good ou excellent, afficher une reussite calme.',
                        'Si status vaut needs_practice, afficher les conseils sans culpabiliser.',
                        'Si la reponse est 503, afficher que la correction IA est indisponible pour le moment.',
                        'Ne pas exposer la cle Gemini dans Flutter.',
                    ],
                ],
                [
                    'title' => 'Scenario 16 - Learner utilise Smart Abstract',
                    'goal' => 'Permettre a l apprenante de resumer un texte et de recevoir une evaluation IA.',
                    'user_story' => 'L utilisatrice lit un paragraphe, ecrit un resume court, puis l app lui dit si les idees importantes sont presentes.',
                    'frontend_tasks' => [
                        'Appeler GET /api/learning-modes/smart-abstract/exercises ou GET /api/smart-abstract-exercises.',
                        'Afficher source_text, instructions, min_words et max_words.',
                        'Ajouter un champ texte pour le resume.',
                        'Afficher un compteur de mots simple si possible.',
                        'Appeler POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate avec summary.',
                        'Afficher data.result.score, improved_summary, missing_ideas, strengths et feedback.',
                        'Appeler GET /api/me/smart-abstract-progress pour actualiser les statistiques.',
                    ],
                    'api_flow' => [
                        'GET /api/smart-abstract-exercises',
                        'POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate',
                        'Le backend garde le texte source officiel.',
                        'Le backend appelle Gemini si AI_PROVIDER vaut gemini.',
                        'Le backend sauvegarde smart_abstract_attempts.',
                        'Le backend retourne le resultat structure pour Flutter.',
                    ],
                    'display_rules' => [
                        'Afficher les idees manquantes comme des pistes de correction.',
                        'Afficher improved_summary comme une proposition, pas comme une punition.',
                        'Si la reponse est 503, permettre a l utilisatrice de reessayer plus tard.',
                        'Ne jamais appeler Gemini depuis l app mobile.',
                    ],
                ],
            ],
            'real_cases' => [
                [
                    'title' => 'Une apprenante commence un exercice de lecture',
                    'actor' => 'learner',
                    'situation' => 'Marie ouvre Practice Exercises, choisit Reading, ecoute la phrase, lit a voix haute et recoit un score.',
                    'frontend_actions' => [
                        'Verifier que le compte connecte a le role learner.',
                        'Afficher les modes depuis le backend.',
                        'Charger les exercices du mode reading.',
                        'Utiliser le TTS du telephone pour le bouton Listen.',
                        'Utiliser le STT du telephone pour obtenir le transcript.',
                        'Envoyer uniquement le transcript au backend.',
                        'Afficher les mots corrects, manquants, incorrects ou en trop.',
                    ],
                    'routes' => [
                        'GET /api/learning-modes',
                        'GET /api/learning-modes/reading/exercises',
                        'POST /api/reading-exercises/{readingExercise}/evaluate',
                        'GET /api/me/reading-progress',
                    ],
                    'backend_guarantees' => [
                        'Le texte officiel reste en base de donnees.',
                        'La tentative est sauvegardee dans reading_exercise_attempts.',
                        'La progression reading est mise a jour automatiquement.',
                    ],
                ],
                [
                    'title' => 'Une apprenante utilise Writing Assistant',
                    'actor' => 'learner',
                    'situation' => 'Marie ecrit quelques phrases en anglais et demande une correction douce avec IA.',
                    'frontend_actions' => [
                        'Charger les exercices writing.',
                        'Afficher prompt, instructions et nombre minimum de mots.',
                        'Garder le texte dans le champ si l API echoue.',
                        'Envoyer answer au backend.',
                        'Afficher score, corrected_text, mistakes, suggestions et feedback.',
                    ],
                    'routes' => [
                        'GET /api/learning-modes/writing/exercises',
                        'POST /api/writing-exercises/{writingExercise}/evaluate',
                        'GET /api/me/writing-progress',
                    ],
                    'backend_guarantees' => [
                        'La cle Gemini reste cote Laravel.',
                        'Le backend appelle Gemini et normalise la reponse.',
                        'La tentative est sauvegardee dans writing_exercise_attempts.',
                    ],
                ],
                [
                    'title' => 'Une apprenante resume un texte avec Smart Abstract',
                    'actor' => 'learner',
                    'situation' => 'Marie lit un paragraphe, ecrit un resume court et recoit les idees manquantes.',
                    'frontend_actions' => [
                        'Charger les exercices smart abstract.',
                        'Afficher le texte source et les consignes.',
                        'Afficher min_words et max_words.',
                        'Envoyer summary au backend.',
                        'Afficher improved_summary, missing_ideas, strengths et feedback.',
                    ],
                    'routes' => [
                        'GET /api/learning-modes/smart-abstract/exercises',
                        'POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate',
                        'GET /api/me/smart-abstract-progress',
                    ],
                    'backend_guarantees' => [
                        'Le texte source officiel reste en base.',
                        'Gemini evalue le resume cote serveur.',
                        'La tentative est sauvegardee dans smart_abstract_attempts.',
                    ],
                ],
                [
                    'title' => 'Un learner veut etre suivi par un tutor',
                    'actor' => 'learner + tutor',
                    'situation' => 'Marie genere un code, le donne a son tutor, puis le tutor l associe a son compte.',
                    'frontend_actions' => [
                        'Le learner affiche ou genere son code actif.',
                        'Le learner peut annuler ou regenerer le code.',
                        'Le tutor saisit le code recu.',
                        'Le frontend tutor recharge la liste des learners apres association.',
                    ],
                    'routes' => [
                        'GET /api/me/association-code',
                        'POST /api/me/association-code',
                        'POST /api/me/association-code/regenerate',
                        'DELETE /api/me/association-code',
                        'POST /api/tutor/learners/link',
                        'GET /api/tutor/learners',
                    ],
                    'backend_guarantees' => [
                        'Un code expire apres 24h.',
                        'Un code utilise ou annule ne peut plus etre reutilise.',
                        'Le lien tutor/learner est stocke dans tutor_learners.',
                    ],
                ],
                [
                    'title' => 'Un tutor repere les learners a aider',
                    'actor' => 'tutor',
                    'situation' => 'Le tutor ouvre son dashboard et voit les apprenants sans tentative ou avec une moyenne faible.',
                    'frontend_actions' => [
                        'Charger le dashboard tutor au demarrage de l espace tutor.',
                        'Afficher total_learners, active_learners et les moyennes par module.',
                        'Afficher learners_needing_attention.',
                        'Ouvrir la progression detaillee quand le tutor clique sur un learner.',
                        'Afficher les historiques reading, writing et smart abstract.',
                    ],
                    'routes' => [
                        'GET /api/tutor/dashboard',
                        'GET /api/tutor/learners',
                        'GET /api/tutor/learners/{learner}/progress',
                        'GET /api/tutor/learners/{learner}/reading-attempts',
                        'GET /api/tutor/learners/{learner}/writing-attempts',
                        'GET /api/tutor/learners/{learner}/smart-abstract-attempts',
                    ],
                    'backend_guarantees' => [
                        'Le tutor ne voit que ses learners associes.',
                        'Une tentative reste liee au learner meme si le tutor le detache.',
                        'La progression detaillee est filtree par association tutor/learner.',
                    ],
                ],
                [
                    'title' => 'Un admin surveille l application',
                    'actor' => 'admin',
                    'situation' => 'L admin ouvre son dashboard web pour suivre les comptes, les roles et l activite globale.',
                    'frontend_actions' => [
                        'Connecter l admin depuis /admin/login.',
                        'Verifier que role vaut admin avant de charger /admin/dashboard.',
                        'Afficher les statistiques users et learning.',
                        'Lister et filtrer les utilisateurs.',
                        'Changer un role utilisateur si necessaire.',
                    ],
                    'routes' => [
                        'POST /api/auth/login',
                        'GET /api/auth/me',
                        'GET /api/admin/dashboard',
                        'GET /api/admin/users',
                        'PATCH /api/admin/users/{user}/role',
                        'PATCH /api/admin/users/{user}/conversation-limits',
                    ],
                    'backend_guarantees' => [
                        'Toutes les routes admin demandent le role admin.',
                        'Un admin ne peut pas retirer son propre role admin.',
                        'La limite conversation IA par defaut vaut 180 secondes par session et 3 sessions par jour.',
                        'Le dashboard compte reading, writing et smart abstract.',
                    ],
                ],
                [
                    'title' => 'Gemini ne repond pas',
                    'actor' => 'learner',
                    'situation' => 'La cle est invalide, internet est coupe ou le modele Gemini est refuse.',
                    'frontend_actions' => [
                        'Afficher un message simple si la route retourne 503.',
                        'Ne pas vider le champ texte de l apprenante.',
                        'Proposer de reessayer plus tard.',
                        'Ne jamais afficher les details techniques a l enfant.',
                    ],
                    'routes' => [
                        'POST /api/writing-exercises/{writingExercise}/evaluate',
                        'POST /api/smart-abstract-exercises/{smartAbstractExercise}/evaluate',
                    ],
                    'backend_guarantees' => [
                        'L erreur IA est transformee en JSON propre.',
                        'La cle Gemini n est jamais exposee au mobile.',
                        'Les tests restent stables avec AI_PROVIDER=fake.',
                    ],
                ],
                [
                    'title' => 'Paiement a preparer plus tard',
                    'actor' => 'learner + admin',
                    'situation' => 'Certaines fonctionnalites pourraient devenir premium apres choix de l agregateur de paiement.',
                    'frontend_actions' => [
                        'Afficher clairement les modules gratuits et premium.',
                        'Rediriger vers le paiement seulement si le backend dit que l acces est bloque.',
                        'Ne jamais decider cote Flutter qu un utilisateur est premium sans validation backend.',
                    ],
                    'routes' => [
                        'A definir apres choix du provider de paiement',
                    ],
                    'backend_guarantees' => [
                        'Creer la session de paiement cote serveur.',
                        'Recevoir les webhooks du provider.',
                        'Sauvegarder le statut paiement.',
                        'Bloquer ou debloquer les modules premium depuis Laravel.',
                    ],
                ],
            ],
            'flutter_examples' => [
                [
                    'title' => 'Packages Flutter a ajouter',
                    'language' => 'bash',
                    'code' => <<<'CODE'
flutter pub add http flutter_secure_storage

# Pour le module Reading Practice cote mobile
flutter pub add flutter_tts speech_to_text
CODE,
                ],
                [
                    'title' => 'Choisir la bonne baseUrl',
                    'language' => 'dart',
                    'code' => <<<'CODE'
// Android emulator
const String baseUrl = 'http://10.0.2.2:8000/api';

// iOS simulator
const String baseUrl = 'http://localhost:8000/api';

// Telephone physique sur le meme Wi-Fi que ton ordinateur
// Remplace 192.168.1.20 par l'adresse IP locale de ton ordinateur.
const String baseUrl = 'http://192.168.1.20:8000/api';
CODE,
                ],
                [
                    'title' => 'Service Flutter complet pour consommer le module auth',
                    'language' => 'dart',
                    'code' => <<<'CODE'
import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class AuthApiService {
  AuthApiService({
    http.Client? client,
    FlutterSecureStorage? storage,
  })  : _client = client ?? http.Client(),
        _storage = storage ?? const FlutterSecureStorage();

  static const String baseUrl = 'http://10.0.2.2:8000/api';
  static const String _tokenKey = 'auth_token';

  final http.Client _client;
  final FlutterSecureStorage _storage;

  Map<String, String> get _jsonHeaders => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      };

  Future<Map<String, dynamic>> register({
    required String fullName,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _client.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: _jsonHeaders,
      body: jsonEncode({
        'full_name': fullName,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'device_name': 'flutter-app',
      }),
    );

    final data = _decodeResponse(response);
    await _saveTokenFromResponse(data);

    return data;
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _client.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: _jsonHeaders,
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': 'flutter-app',
      }),
    );

    final data = _decodeResponse(response);
    await _saveTokenFromResponse(data);

    return data;
  }

  Future<Map<String, dynamic>> me() async {
    final response = await _client.get(
      Uri.parse('$baseUrl/auth/me'),
      headers: await _authHeaders(),
    );

    return _decodeResponse(response);
  }

  Future<void> logout() async {
    final response = await _client.post(
      Uri.parse('$baseUrl/auth/logout'),
      headers: await _authHeaders(),
    );

    _decodeResponse(response);
    await _storage.delete(key: _tokenKey);
  }

  Future<Map<String, String>> _authHeaders() async {
    final token = await _storage.read(key: _tokenKey);

    return {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Future<void> _saveTokenFromResponse(Map<String, dynamic> data) async {
    final token = data['data']?['token']?['access_token'] as String?;

    if (token != null) {
      await _storage.write(key: _tokenKey, value: token);
    }
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    final data = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    }

    throw Exception(data['message'] ?? 'Erreur API');
  }
}
CODE,
                ],
                [
                    'title' => 'Exemple d appel depuis une page Flutter',
                    'language' => 'dart',
                    'code' => <<<'CODE'
final authApi = AuthApiService();

try {
  final response = await authApi.login(
    email: 'marie@example.com',
    password: 'password123',
  );

  final user = response['data']['user'];
  print('Connecte: ${user['full_name']}');
} catch (error) {
  print('Erreur de connexion: $error');
}
CODE,
                ],
                [
                    'title' => 'Evaluer une lecture depuis Flutter',
                    'language' => 'dart',
                    'code' => <<<'CODE'
final response = await client.post(
  Uri.parse('$baseUrl/reading-exercises/1/evaluate'),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token',
  },
  body: jsonEncode({
    'transcript': 'The children visited beautiful museum yesterday.',
  }),
);

final data = jsonDecode(response.body) as Map<String, dynamic>;
final result = data['data']['result'];

print('Score: ${result['score']}');
print('Feedback: ${result['feedback']['message']}');
CODE,
                ],
            ],
        ];

        return view('docs.api', [
            'documentation' => $documentation,
            'markdownDocumentation' => $this->toMarkdown($documentation),
        ]);
    }

    /**
     * @param  array<string, mixed>  $documentation
     */
    private function toMarkdown(array $documentation): string
    {
        $lines = [
            '# '.$documentation['title'],
            '',
            '- Version: '.$documentation['version'],
            '- Base URL: `'.$documentation['base_url'].'`',
            '- Authentication: '.$documentation['authentication_type'],
            '',
            '## Headers importants',
            '',
            $this->codeBlock('json', $documentation['important_headers']),
            '',
            '## Etapes cote frontend',
            '',
        ];

        foreach ($documentation['frontend_steps'] as $index => $step) {
            $lines[] = ($index + 1).'. '.$step;
        }

        $lines[] = '';
        $lines[] = '## Roles utilisateur';
        $lines[] = '';
        $lines[] = 'Le backend gere les roles `learner`, `tutor` et `admin`. Le role `visitor` n est pas stocke en base: il represente simplement une personne non connectee.';
        $lines[] = '';

        foreach ($documentation['roles'] as $role) {
            $lines[] = '### '.$role['name'];
            $lines[] = '';
            $lines[] = $role['description'];
            $lines[] = '';
            $lines[] = '- Stocke en base: '.($role['stored_in_database'] ? 'oui' : 'non');
            $lines[] = '';
            $lines[] = 'Permissions principales:';
            $lines[] = '';

            foreach ($role['permissions'] as $permission) {
                $lines[] = '- '.$permission;
            }

            $lines[] = '';
        }

        $lines[] = '';
        $lines[] = '## Scenarios et role du frontend';
        $lines[] = '';

        foreach ($documentation['feature_guides'] as $guide) {
            $lines[] = '### '.$guide['title'];
            $lines[] = '';
            $lines[] = '**Objectif:** '.$guide['goal'];
            $lines[] = '';
            $lines[] = '**Scenario utilisateur:** '.$guide['user_story'];
            $lines[] = '';
            $lines[] = '#### Ce que le frontend doit faire';
            $lines[] = '';

            foreach ($guide['frontend_tasks'] as $task) {
                $lines[] = '- '.$task;
            }

            $lines[] = '';
            $lines[] = '#### Parcours API';
            $lines[] = '';

            foreach ($guide['api_flow'] as $step) {
                $lines[] = '- '.$step;
            }

            $lines[] = '';
            $lines[] = '#### Regles d affichage';
            $lines[] = '';

            foreach ($guide['display_rules'] as $rule) {
                $lines[] = '- '.$rule;
            }

            $lines[] = '';
        }

        $lines[] = '';
        $lines[] = '## Cas reels importants';
        $lines[] = '';
        $lines[] = 'Ces cas montrent comment les fonctionnalites se comportent dans une vraie utilisation de LexiCoach.';
        $lines[] = '';

        foreach ($documentation['real_cases'] as $case) {
            $lines[] = '### '.$case['title'];
            $lines[] = '';
            $lines[] = '- Acteur: `'.$case['actor'].'`';
            $lines[] = '- Situation: '.$case['situation'];
            $lines[] = '';
            $lines[] = '#### Actions frontend';
            $lines[] = '';

            foreach ($case['frontend_actions'] as $action) {
                $lines[] = '- '.$action;
            }

            $lines[] = '';
            $lines[] = '#### Routes utilisees';
            $lines[] = '';

            foreach ($case['routes'] as $route) {
                $lines[] = '- `'.$route.'`';
            }

            $lines[] = '';
            $lines[] = '#### Garanties backend';
            $lines[] = '';

            foreach ($case['backend_guarantees'] as $guarantee) {
                $lines[] = '- '.$guarantee;
            }

            $lines[] = '';
        }

        foreach ($documentation['modules'] as $module) {
            $lines[] = '## '.$module['name'];
            $lines[] = '';
            $lines[] = $module['description'];
            $lines[] = '';

            foreach ($module['endpoints'] as $endpoint) {
                $lines[] = '### '.$endpoint['name'];
                $lines[] = '';
                $lines[] = '- Method: `'.$endpoint['method'].'`';
                $lines[] = '- Path: `'.$endpoint['path'].'`';
                $lines[] = '- Protected: '.($endpoint['protected'] ? 'oui, token requis' : 'non, route publique');
                $lines[] = '';
                $lines[] = $endpoint['description'];
                $lines[] = '';
                $lines[] = '#### Headers';
                $lines[] = '';
                $lines[] = $this->codeBlock('json', $endpoint['headers']);
                $lines[] = '';
                $lines[] = '#### Body a envoyer';
                $lines[] = '';
                $lines[] = $endpoint['request_body'] === null
                    ? 'Aucun body'
                    : $this->codeBlock('json', $endpoint['request_body']);
                $lines[] = '';

                foreach ($endpoint['responses'] as $response) {
                    $lines[] = '#### Reponse '.$response['status'].' - '.$response['title'];
                    $lines[] = '';
                    $lines[] = $this->codeBlock('json', $response['body']);
                    $lines[] = '';
                }
            }
        }

        $lines[] = '## Exemples Flutter';
        $lines[] = '';
        $lines[] = 'Ces exemples montrent comment une app Flutter peut appeler le backend, recuperer le token Sanctum et l utiliser sur les routes protegees.';
        $lines[] = '';

        foreach ($documentation['flutter_examples'] as $example) {
            $lines[] = '### '.$example['title'];
            $lines[] = '';
            $lines[] = $this->codeBlock($example['language'], $example['code']);
            $lines[] = '';
        }

        return trim(implode("\n", $lines))."\n";
    }

    /**
     * @param  array<string, mixed>|string|null  $content
     */
    private function codeBlock(string $language, array|string|null $content): string
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return "```{$language}\n{$content}\n```";
    }
}
