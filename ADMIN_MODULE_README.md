# Module Admin Dashboard

Ce module permet a un administrateur de suivre l application, de gerer les roles des utilisateurs et de modifier les limites de conversation IA.

Il contient deux parties:

```text
API Admin protegee par role admin
SPA web Blade /admin/dashboard
```

La partie web admin est faite avec deux pages Blade autonomes dans le backend Laravel. Elles consomment les routes API avec JavaScript et ne touchent pas a l application mobile.

## Acces

Un admin doit se connecter avec:

```http
POST /api/auth/login
```

Puis envoyer le token sur les routes admin:

```text
Authorization: Bearer {access_token}
Accept: application/json
```

Si le user connecte n a pas le role `admin`, le backend retourne `403 Forbidden`.

Apres `php artisan migrate --seed`, un compte admin de demo est disponible:

```text
email: admin@example.com
password: password123
```

## Routes disponibles

### Dashboard admin

```http
GET /api/admin/dashboard
```

Cette route retourne:

- le nombre total d utilisateurs;
- le nombre de learners;
- le nombre de tutors;
- le nombre d admins;
- le nombre de learning modes;
- le nombre d exercices reading;
- le nombre de tentatives reading;
- la moyenne reading globale;
- le meilleur score reading;
- le nombre d exercices writing;
- le nombre de tentatives writing;
- la moyenne writing globale;
- le nombre d exercices smart abstract;
- le nombre de tentatives smart abstract;
- la moyenne smart abstract globale;
- le nombre d associations tutor/learner;
- les dernieres tentatives.

Exemple de reponse:

```json
{
  "success": true,
  "message": "Dashboard admin recupere.",
  "data": {
    "dashboard": {
      "users": {
        "total": 25,
        "learners": 18,
        "tutors": 5,
        "admins": 2,
        "recent": []
      },
      "learning": {
        "modes": 5,
        "reading_exercises": 12,
        "reading_attempts": 80,
        "average_reading_score": 74,
        "best_reading_score": 100,
        "writing_exercises": 6,
        "writing_attempts": 32,
        "average_writing_score": 78,
        "smart_abstract_exercises": 5,
        "smart_abstract_attempts": 18,
        "average_smart_abstract_score": 81
      },
      "tutor_view": {
        "linked_pairs": 10,
        "tutors_with_learners": 4
      },
      "recent_attempts": []
    }
  }
}
```

### Lister les utilisateurs

```http
GET /api/admin/users
```

Filtres optionnels:

```text
GET /api/admin/users?role=tutor
GET /api/admin/users?search=marie
GET /api/admin/users?role=learner&search=marie
```

La route retourne les 100 comptes les plus recents.

### Voir un utilisateur

```http
GET /api/admin/users/{user}
```

`{user}` est l id de l utilisateur.

La reponse contient aussi les limites de conversation IA:

```json
{
  "conversation_limits": {
    "session_limit_seconds": 180,
    "session_limit_minutes": 3,
    "daily_session_limit": 3
  }
}
```

### Modifier le role d un utilisateur

```http
PATCH /api/admin/users/{user}/role
```

Body:

```json
{
  "role": "tutor"
}
```

Roles acceptes:

```text
learner
tutor
admin
```

Regle importante: un admin ne peut pas retirer son propre role admin.

### Modifier les limites de conversation IA

```http
PATCH /api/admin/users/{user}/conversation-limits
```

Body:

```json
{
  "ai_conversation_session_limit_seconds": 180,
  "ai_conversation_daily_session_limit": 3
}
```

Regles:

- la duree par session est en secondes;
- la valeur par defaut est `180`, donc 3 minutes par session;
- la duree minimum acceptee est 60 secondes;
- la duree maximum acceptee est 3600 secondes;
- `ai_conversation_daily_session_limit` indique combien de sessions IA le user peut lancer par jour;
- `0` session par jour permet de bloquer temporairement la conversation IA pour ce user.

Cette route prepare le futur module realtime conversation. Quand le module sera branche, le backend devra lire ces limites avant de creer une session Gemini Live.

## SPA web

La page de connexion est disponible ici:

```text
/admin/login
```

Le dashboard est disponible ici:

```text
/admin/dashboard
```

Le parcours est volontairement separe:

1. `/admin/login` affiche seulement le formulaire de connexion admin.
2. La page appelle `POST /api/auth/login`.
3. Si le role vaut `admin`, elle sauvegarde le token et redirige vers `/admin/dashboard`.
4. `/admin/dashboard` verifie le role avec `GET /api/auth/me`.
5. Le dashboard charge `GET /api/admin/dashboard`.
6. Le dashboard charge `GET /api/admin/users`.
7. Le dashboard permet de filtrer les comptes.
8. Le dashboard permet de changer le role d un utilisateur.
9. Le dashboard permet de modifier les limites de conversation IA d un utilisateur.

Si un visiteur ouvre `/admin/dashboard` sans token, la page redirige vers `/admin/login`.

## Design

Le dashboard utilise:

- Tailwind CSS;
- Blade avec JavaScript;
- les couleurs du mobile: `#F9F7FF`, `#6547E8`, `#222033`, `#77718A`, `#E2DDF2`;
- pas de gradient;
- des boutons capsule et champs arrondis dans un style proche de Cupertino;
- une interface simple en SPA.

## Fichiers importants

```text
routes/api.php
routes/web.php
app/Http/Controllers/Api/AdminDashboardController.php
app/Http/Controllers/Api/AdminUserController.php
app/Services/AdminDashboardSummary.php
resources/views/admin/login.blade.php
resources/views/admin/dashboard.blade.php
tests/Feature/AdminApiTest.php
```
