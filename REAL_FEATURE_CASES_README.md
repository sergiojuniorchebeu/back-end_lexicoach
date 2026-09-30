# Cas reels des fonctionnalites importantes

Ce document explique les cas concrets que l application doit couvrir. Il sert surtout a aider la personne cote frontend a comprendre quand appeler chaque route API.

## Cas 1 - Une apprenante commence un exercice de lecture

Situation reelle: Marie ouvre l application, choisit Practice Exercises, puis Reading.

Ce que le frontend fait:

1. Verifier que Marie est connectee avec le role `learner`.
2. Appeler `GET /api/learning-modes`.
3. Quand Marie clique sur Reading, appeler `GET /api/learning-modes/reading/exercises`.
4. Afficher le texte officiel de l exercice.
5. Utiliser `flutter_tts` si Marie appuie sur Listen.
6. Utiliser `speech_to_text` si Marie appuie sur le micro.
7. Envoyer le transcript a `POST /api/reading-exercises/{id}/evaluate`.
8. Afficher le score et les mots corrects/manquants/incorrects.

Ce que le backend garantit:

- le texte officiel vient de la base de donnees;
- Flutter n envoie pas `expected_text`;
- chaque tentative est sauvegardee;
- la progression reading est mise a jour.

## Cas 2 - Une apprenante ecrit une reponse avec Writing Assistant

Situation reelle: Marie doit ecrire quelques phrases en anglais sur sa journee.

Ce que le frontend fait:

1. Appeler `GET /api/learning-modes/writing/exercises`.
2. Afficher `prompt`, `instructions` et `min_words`.
3. Laisser Marie ecrire son texte dans un champ confortable.
4. Bloquer l envoi si le texte est vide.
5. Envoyer `answer` a `POST /api/writing-exercises/{id}/evaluate`.
6. Afficher `score`, `corrected_text`, `mistakes`, `suggestions` et `feedback`.
7. Recharger `GET /api/me/writing-progress`.

Ce que le backend garantit:

- la cle Gemini reste dans Laravel;
- Flutter ne parle pas directement a Gemini;
- la correction IA est sauvegardee dans `writing_exercise_attempts`;
- si Gemini est indisponible, l API retourne une erreur `503` propre.

## Cas 3 - Une apprenante resume un texte avec Smart Abstract

Situation reelle: Marie lit un paragraphe, puis doit le resumer simplement.

Ce que le frontend fait:

1. Appeler `GET /api/learning-modes/smart-abstract/exercises`.
2. Afficher `source_text`, `instructions`, `min_words` et `max_words`.
3. Laisser Marie ecrire son resume.
4. Envoyer `summary` a `POST /api/smart-abstract-exercises/{id}/evaluate`.
5. Afficher `score`, `improved_summary`, `missing_ideas`, `strengths` et `feedback`.
6. Recharger `GET /api/me/smart-abstract-progress`.

Ce que le backend garantit:

- le texte source officiel reste en base;
- Gemini analyse le resume cote serveur;
- la tentative est sauvegardee dans `smart_abstract_attempts`;
- la progression smart abstract est mise a jour.

## Cas 4 - Un learner veut etre suivi par un tutor

Situation reelle: Marie veut que son professeur puisse voir ses progres.

Ce que le frontend learner fait:

1. Appeler `GET /api/me/association-code`.
2. Si aucun code actif n existe, appeler `POST /api/me/association-code`.
3. Afficher le code, par exemple `LC-123456`.
4. Afficher la date d expiration.
5. Permettre a Marie d annuler le code avec `DELETE /api/me/association-code`.
6. Permettre a Marie de regenerer le code avec `POST /api/me/association-code/regenerate`.

Ce que le frontend tutor fait:

1. Le tutor saisit le code donne par Marie.
2. Le frontend appelle `POST /api/tutor/learners/link`.
3. Si la reponse reussit, afficher Marie dans la liste des learners.
4. Recharger `GET /api/tutor/learners`.

Ce que le backend garantit:

- un code expire apres 24h;
- un code utilise ne peut pas etre reutilise;
- un code annule ne peut pas etre reutilise;
- un tutor ne voit que les learners associes a lui.

## Cas 5 - Un tutor repere un learner en difficulte

Situation reelle: le tutor ouvre son dashboard et veut savoir qui aider en priorite.

Ce que le frontend fait:

1. Appeler `GET /api/tutor/dashboard`.
2. Afficher `total_learners` et `active_learners`.
3. Afficher les moyennes reading, writing et smart abstract.
4. Afficher `learners_needing_attention`.
5. Quand le tutor clique sur un learner, appeler `GET /api/tutor/learners/{learner}/progress`.
6. Charger les historiques selon le module:
   - `GET /api/tutor/learners/{learner}/reading-attempts`
   - `GET /api/tutor/learners/{learner}/writing-attempts`
   - `GET /api/tutor/learners/{learner}/smart-abstract-attempts`

Ce que le backend garantit:

- les donnees sont filtrees par tutor connecte;
- un tutor ne peut pas acceder a un learner non associe;
- les tentatives restent liees au learner meme si le tutor le detache.

## Cas 6 - Un admin surveille l application

Situation reelle: l admin veut voir si l application est utilisee et corriger les roles des comptes.

Ce que l interface admin fait:

1. Ouvrir `/admin/login`.
2. Se connecter avec un compte `admin`.
3. Rediriger vers `/admin/dashboard`.
4. Appeler `GET /api/admin/dashboard`.
5. Afficher les stats users, learning, tutor view et recent attempts.
6. Appeler `GET /api/admin/users`.
7. Filtrer par role ou recherche.
8. Changer un role avec `PATCH /api/admin/users/{user}/role`.

Ce que le backend garantit:

- les routes admin demandent le role `admin`;
- un admin ne peut pas retirer son propre role admin;
- le dashboard compte les exercices et tentatives reading, writing et smart abstract.

## Cas 7 - Gemini ne repond pas

Situation reelle: internet est coupe, la cle API est invalide ou Gemini refuse le modele.

Ce que le frontend fait:

1. Appeler normalement la route d evaluation writing ou smart abstract.
2. Si l API retourne `503`, afficher un message simple.
3. Garder le texte de l apprenante dans le champ pour qu elle ne perde rien.
4. Proposer de reessayer plus tard.

Message conseille:

```text
La correction IA est indisponible pour le moment. Reessaie plus tard.
```

Ce que le backend garantit:

- l erreur IA est transformee en reponse JSON propre;
- la cle Gemini n est jamais exposee au mobile;
- les tests utilisent `AI_PROVIDER=fake` pour rester stables.

## Cas 8 - Paiement pas encore implemente

Situation reelle: plus tard, certaines fonctionnalites pourraient devenir premium.

Decision a prendre avant implementation:

- provider: Stripe, PayPal, Mobile Money ou agregateur local;
- modele: abonnement, paiement unique ou pack;
- regles: quels modules sont gratuits et quels modules sont premium;
- comportement frontend: que montrer quand un learner n a pas encore paye.

Ce que le backend devra faire plus tard:

- creer une session de paiement;
- recevoir les webhooks du provider;
- sauvegarder le statut paiement;
- bloquer/debloquer les fonctionnalites premium;
- donner a l admin une vue des paiements.
