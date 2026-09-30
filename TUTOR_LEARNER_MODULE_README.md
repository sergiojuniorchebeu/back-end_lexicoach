# Module Tutor / Learner Progression

Ce module permet a un tuteur de suivre la progression d un apprenant.

L idee est simple:

```text
Learner genere un code
        |
        v
Learner donne le code au tutor
        |
        v
Tutor saisit le code
        |
        v
Backend cree le lien tutor <-> learner
        |
        v
Tutor peut voir la progression du learner associe
```

Le frontend mobile n a pas besoin de deviner les relations. Le backend garde la source de verite.

## Roles concernes

### learner

Le learner peut:

- generer un code d association;
- voir son code actif;
- faire les exercices reading;
- construire sa propre progression avec ses tentatives.

### tutor

Le tutor peut:

- entrer le code donne par un learner;
- voir la liste de ses learners associes;
- voir la progression globale d un learner associe;
- voir les tentatives reading, writing et smart abstract d un learner associe.

Le tutor ne peut pas voir un learner qui n est pas associe a lui.

## Tables ajoutees

### learner_association_codes

Cette table garde les codes temporaires generes par les learners.

```text
id
learner_id
code
expires_at
used_at
cancelled_at
created_at
updated_at
```

Regles:

- un code appartient a un seul learner;
- un code expire apres 24h;
- un code utilise recoit une valeur dans `used_at`;
- un code annule recoit une valeur dans `cancelled_at`;
- un code deja utilise ne peut plus etre reutilise.
- un code annule ne peut plus etre reutilise.

### tutor_learners

Cette table fait le lien entre tutors et learners.

```text
id
tutor_id
learner_id
created_at
updated_at
```

Regles:

- un tutor peut suivre plusieurs learners;
- un learner peut etre suivi par plusieurs tutors;
- le meme couple tutor/learner ne peut pas etre duplique.

## Routes learner

Toutes ces routes demandent:

```text
Authorization: Bearer {access_token}
Accept: application/json
```

Le token doit appartenir a un utilisateur avec le role `learner`.

### Voir le code actif

```http
GET /api/me/association-code
```

Si un code actif existe:

```json
{
  "success": true,
  "message": "Code d association actif recupere.",
  "data": {
    "association_code": {
      "id": 1,
      "code": "LC-123456",
      "expires_at": "2026-09-04T10:00:00.000000Z",
      "used_at": null,
      "cancelled_at": null
    }
  }
}
```

Si aucun code actif n existe:

```json
{
  "success": true,
  "message": "Aucun code d association actif.",
  "data": {
    "association_code": null
  }
}
```

### Generer un code

```http
POST /api/me/association-code
```

Body: aucun.

Reponse:

```json
{
  "success": true,
  "message": "Code d association genere.",
  "data": {
    "association_code": {
      "id": 1,
      "code": "LC-123456",
      "expires_at": "2026-09-04T10:00:00.000000Z",
      "used_at": null,
      "cancelled_at": null
    }
  }
}
```

Si le learner a deja un code actif, le backend retourne ce code au lieu d en creer un autre.

### Regenerer un code

```http
POST /api/me/association-code/regenerate
```

Cette route annule le code actif, puis cree un nouveau code.

Reponse:

```json
{
  "success": true,
  "message": "Code d association regenere.",
  "data": {
    "association_code": {
      "id": 2,
      "code": "LC-654321",
      "expires_at": "2026-09-04T10:00:00.000000Z",
      "used_at": null,
      "cancelled_at": null
    }
  }
}
```

### Annuler le code actif

```http
DELETE /api/me/association-code
```

Cette route annule le code actif sans en creer un nouveau.

Reponse:

```json
{
  "success": true,
  "message": "Code d association annule.",
  "data": {
    "association_code": {
      "id": 1,
      "code": "LC-123456",
      "expires_at": "2026-09-04T10:00:00.000000Z",
      "used_at": null,
      "cancelled_at": "2026-09-03T10:30:00.000000Z"
    }
  }
}
```

## Routes tutor

Toutes ces routes demandent:

```text
Authorization: Bearer {access_token}
Accept: application/json
```

Le token doit appartenir a un utilisateur avec le role `tutor`.

### Dashboard tutor

```http
GET /api/tutor/dashboard
```

Cette route donne une vue globale de tous les learners associes au tutor.

Reponse:

```json
{
  "success": true,
  "message": "Dashboard tutor recupere.",
  "data": {
    "dashboard": {
      "total_learners": 4,
      "active_learners": 3,
      "reading": {
        "total_attempts": 18,
        "average_score": 76,
        "best_score": 100,
        "latest_attempts": [],
        "learners_needing_attention": []
      },
      "progress_by_mode": {
        "reading": {
          "learning_mode": {
            "id": 1,
            "name": "Reading Practice",
            "slug": "reading"
          },
          "summary": {
            "total_attempts": 18,
            "average_score": 76,
            "best_score": 100,
            "latest_attempts": []
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
            "average_score": 0,
            "best_score": 0,
            "latest_attempts": []
          }
        }
      }
    }
  }
}
```

Les modes `reading`, `writing` et `smart-abstract` contiennent maintenant les vraies donnees de progression. Les autres modes restent a zero jusqu a leur implementation.

### Associer un learner

```http
POST /api/tutor/learners/link
```

Body:

```json
{
  "code": "LC-123456"
}
```

Reponse:

```json
{
  "success": true,
  "message": "Learner associe au tutor.",
  "data": {
    "learner": {
      "id": 2,
      "full_name": "Marie Dupont",
      "email": "marie@example.com",
      "role": "learner"
    }
  }
}
```

Erreurs possibles:

- `422`: code invalide, expire ou deja utilise;
- `403`: le compte connecte n est pas un tutor;
- `401`: aucun token valide.

### Lister les learners associes

```http
GET /api/tutor/learners
```

Reponse:

```json
{
  "success": true,
  "message": "Learners du tutor recuperes.",
  "data": {
    "learners": [
      {
        "id": 2,
        "full_name": "Marie Dupont",
        "email": "marie@example.com",
        "role": "learner"
      }
    ]
  }
}
```

### Voir la progression d un learner

```http
GET /api/tutor/learners/{learner}/progress
```

`{learner}` est l id du learner.

Reponse:

```json
{
  "success": true,
  "message": "Progression du learner recuperee.",
  "data": {
    "learner": {
      "id": 2,
      "full_name": "Marie Dupont",
      "email": "marie@example.com",
      "role": "learner"
    },
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

Si le learner n est pas associe au tutor connecte:

```json
{
  "message": "This learner is not linked to this tutor."
}
```

Le status HTTP sera `403`.

### Detacher un learner

```http
DELETE /api/tutor/learners/{learner}
```

Cette route supprime seulement le lien entre le tutor et le learner.

Elle ne supprime pas:

- le compte du learner;
- les exercices;
- les tentatives;
- la progression personnelle du learner.

Reponse:

```json
{
  "success": true,
  "message": "Learner detache du tutor.",
  "data": null
}
```

### Voir les tentatives de lecture d un learner

```http
GET /api/tutor/learners/{learner}/reading-attempts
```

Reponse:

```json
{
  "success": true,
  "message": "Tentatives de lecture du learner recuperees.",
  "data": {
    "learner": {
      "id": 2,
      "full_name": "Marie Dupont",
      "email": "marie@example.com",
      "role": "learner"
    },
    "attempts": [
      {
        "id": 5,
        "exercise": {
          "id": 1,
          "title": "Garden sentence",
          "text": "The little boy is playing in the garden.",
          "language": "en-US"
        },
        "transcript": "The little boy is playing in the garden.",
        "score": 100,
        "status": "excellent",
        "is_correct": true,
        "feedback": {
          "title": "Excellent!",
          "message": "Your reading was correct."
        },
        "created_at": "2026-09-03T10:00:00.000000Z"
      }
    ]
  }
}
```

### Voir les tentatives writing d un learner

```http
GET /api/tutor/learners/{learner}/writing-attempts
```

Cette route retourne les textes ecrits par le learner, le score IA, le texte corrige, les erreurs et les suggestions.

### Voir les tentatives smart abstract d un learner

```http
GET /api/tutor/learners/{learner}/smart-abstract-attempts
```

Cette route retourne les resumes ecrits par le learner, le score IA, le resume ameliore, les idees manquantes et les points forts.

## Scenarios cote frontend

### Scenario 1 - Le learner veut partager sa progression

1. Le learner est connecte.
2. Flutter verifie que `data.user.role` vaut `learner`.
3. Flutter appelle `GET /api/me/association-code`.
4. Si `association_code` vaut `null`, Flutter appelle `POST /api/me/association-code`.
5. Flutter affiche le code, par exemple `LC-123456`.
6. Le learner donne ce code a son tutor.

Important: le learner ne doit pas inventer le code. Le code vient toujours du backend.

Le learner peut aussi:

- annuler son code avec `DELETE /api/me/association-code`;
- regenerer un nouveau code avec `POST /api/me/association-code/regenerate`.

### Scenario 2 - Le tutor associe le learner

1. Le tutor est connecte.
2. Flutter verifie que `data.user.role` vaut `tutor`.
3. Flutter affiche un champ pour saisir le code.
4. Flutter envoie le code a `POST /api/tutor/learners/link`.
5. Si la reponse reussit, Flutter affiche le learner ajoute.
6. Flutter recharge `GET /api/tutor/learners`.

Si le backend retourne `422`, le code est invalide, expire ou deja utilise.

### Scenario 3 - Le tutor consulte le tableau de bord

1. Flutter appelle `GET /api/tutor/dashboard`.
2. Flutter affiche `total_learners`, `active_learners` et les moyennes reading, writing et smart abstract.
3. Flutter affiche les learners a surveiller avec `learners_needing_attention`.
4. Flutter appelle `GET /api/tutor/learners` pour afficher la liste detaillee.
5. Le tutor clique sur un learner.
6. Flutter appelle `GET /api/tutor/learners/{learner}/progress`.
7. Flutter appelle `GET /api/tutor/learners/{learner}/reading-attempts` pour afficher l historique reading.
8. Flutter appelle `GET /api/tutor/learners/{learner}/writing-attempts` pour afficher l historique writing.
9. Flutter appelle `GET /api/tutor/learners/{learner}/smart-abstract-attempts` pour afficher l historique smart abstract.

Si la liste est vide, Flutter doit afficher un etat vide avec un bouton pour associer un learner.

### Scenario 4 - Le tutor retire un learner de son suivi

1. Flutter demande une confirmation.
2. Flutter appelle `DELETE /api/tutor/learners/{learner}`.
3. Si la reponse reussit, Flutter retire le learner de la liste.
4. Flutter recharge `GET /api/tutor/dashboard`.

Le backend supprime seulement l association. L historique du learner reste intact.

## Fichiers importants

```text
app/Http/Controllers/Api/LearnerAssociationCodeController.php
app/Http/Controllers/Api/TutorDashboardController.php
app/Http/Controllers/Api/TutorLearnerController.php
app/Models/LearnerAssociationCode.php
app/Models/User.php
app/Services/LearningModeProgressSummary.php
app/Services/ReadingProgressSummary.php
app/Services/TutorDashboardSummary.php
database/migrations/2026_09_03_030000_create_learner_association_codes_table.php
database/migrations/2026_09_03_030100_create_tutor_learners_table.php
database/factories/LearnerAssociationCodeFactory.php
tests/Feature/TutorLearnerApiTest.php
routes/api.php
```

## Ce que le backend protege

Le backend verifie:

- que le learner est bien connecte avant de generer un code;
- que seul un tutor peut associer un learner;
- que le code existe;
- que le code n est pas expire;
- que le code n a pas deja ete utilise;
- que le learner consulte par le tutor est vraiment associe a ce tutor.

Cette verification est importante parce que le frontend ne doit jamais etre considere comme une securite suffisante.
