# Guide utilisateur — Plateforme d'audit DGCPT

## 1. Introduction

Ce guide s'adresse aux **auditeurs**, **agents de mission**, **inspecteurs** et contributeurs opérationnels. Il couvre le cycle quotidien : connexion, missions, terrain, risques et suivi.

> **Rappel** : l'IA est **assistive uniquement**. Aucune suggestion IA ne remplace une validation humaine.

---

## 2. Première connexion

### Étape 1 — Accéder à la plateforme

1. Ouvrez l'URL institutionnelle (ex. `https://audit.votre-domaine.sn`).
2. Cliquez sur **Connexion** (`/login`).
3. Saisissez email et mot de passe.
4. Si demandé : complétez le **changement de mot de passe obligatoire** (`/password/changement-obligatoire`).

### Étape 2 — Tableau de bord

Après connexion vous arrivez sur **`/dashboard`** (sauf profils COPRI ou admin redirigés automatiquement).

| Zone | Contenu |
|------|---------|
| Missions | Missions visibles selon votre pôle / rôle |
| Indicateurs | Entretiens, risques, progression |
| Raccourcis | Accès modules terrain |

---

## 3. Navigation principale (menu latéral)

Selon votre profil institutionnel :

| Menu | Route | Usage |
|------|-------|-------|
| Tableau de bord | `/dashboard` | Accueil |
| Missions | `/missions` | Liste des missions |
| Missions (services) | via mission | Services audités |
| Cartographie | `/cartographie` | Sélection mission → heatmap |
| Questionnaires | `/module/questionnaires` | Hub questionnaires |
| Workflows | `/workflow-builder` | Bibliothèque (lecture/création selon droits) |
| Runtime workflows | `/workflows/dashboard` | Missions en cours de workflow |
| SWOT / RACI | builders + mission | Analyse stratégique / gouvernance |
| Copilote IA | `/ai` | Assistance (non contraignante) |
| Entretiens | `/module/entretiens` | Conduite terrain |
| Processus / Actifs / Risques | modules dédiés | Chaîne risque |
| Actions correctives | `/module/actions-correctives` | Suivi |
| Rapports | `/module/rapports` | Reporting |

---

## 4. Cycle mission — pas à pas

### 4.1 Consulter les missions

1. Menu **Missions** → `/missions`.
2. La liste est filtrée : vous ne voyez que les missions **autorisées** (pôle, supervision, équipe).
3. Cliquez sur une mission pour ouvrir la **fiche mission** (`/missions/{id}`).

### 4.2 Créer une mission (superviseurs)

> Réservé aux utilisateurs avec droit `create` (superviseur national ou de pôle).

1. `/missions` → **Nouvelle mission**.
2. Renseignez : organisation, dates, département, chef de mission.
3. Enregistrez → statut initial **Brouillon** ou **En cours**.
4. Un **workflow** peut être attaché automatiquement si configuré pour le département.

### 4.3 Ajouter des services audités

1. Fiche mission → section **Services**.
2. Créez un ou plusieurs services (processus audité).
3. Chaque service pourra recevoir des **entretiens** et des **documents**.

### 4.4 Équipe de mission

1. Fiche mission → **Équipe**.
2. Affectez : chef de mission, inspecteur vérificateur, agents, etc.
3. Les rôles mission déterminent qui peut conduire / valider.

### 4.5 Exécuter le workflow runtime

1. Fiche mission → **Ouvrir workflow** ou `/missions/{id}/workflow/runtime`.
2. Le canvas affiche l'étape courante.
3. Complétez l'étape (formulaire, questionnaire, SWOT, RACI, etc.).
4. Soumettez → progression vers l'étape suivante.
5. Les actions **Approuver / Rejeter / Ignorer** sont tracées (audit).

### 4.6 Conduire un entretien

1. Menu **Entretiens** ou service → **Entretiens**.
2. **Créer un entretien** sur le service.
3. Si requis : attacher un **modèle de questionnaire** (superviseur).
4. Ouvrir **Conduite** (`/entretiens/{id}/conduite`).
5. Répondre aux questions → **Terminer l'entretien**.
6. Les risques identifiés peuvent être promus vers le registre.

### 4.7 Gérer les risques

1. **Processus** → **Actifs** → **Risques** (chaîne classique).
2. Ou **Cartographie** pour vue heatmap.
3. Créer / mettre à jour un risque : criticité, propriétaire, mitigation.
4. **Risk Review Board** (`/risks/review-board`) pour revue collective (si activé).

### 4.8 SWOT et RACI (mission)

Depuis la fiche mission :

| Action | Route |
|--------|-------|
| Ouvrir SWOT | `/missions/{id}/swot` |
| Recommandations SWOT | `/missions/{id}/swot/recommendations` |
| Ouvrir RACI | `/missions/{id}/raci` |
| Analytics RACI | `/missions/{id}/raci/analytics` |

### 4.9 Copilote IA (assistif)

1. Fiche mission → **Copilote mission** (`/ai/missions/{id}`).
2. Posez une question ou lancez **Synthèse audit** / **Analyse risques**.
3. Lisez la suggestion — badge **IA assistive**.
4. **Validez humainement** toute décision ; marquez la recommandation comme revue si proposé.

### 4.10 Rapport et clôture

1. `/missions/{id}/rapport` — génération rapport.
2. Workflow : étape **Reporting** / **Approbation** selon modèle.
3. Transitions de statut mission (IS / COPRI) via gouvernance institutionnelle.

---

## 5. Profils particuliers

| Profil | Comportement |
|--------|----------------|
| **COPRI** | Redirection dashboard exécutif |
| **Admin technique** | Accès console `/admin` |
| **Superviseur global** | Toutes missions, création, validation |
| **Inspecteur pôle** | Missions du département + supervision |

---

## 6. Bonnes pratiques utilisateur

1. Ne jamais partager ses identifiants.
2. Vérifier le **département / mission** avant toute saisie sensible.
3. Traiter les suggestions IA comme des **brouillons**.
4. Documenter les constats dans les modules prévus (pas en commentaires libres hors audit trail).
5. En cas d'erreur de périmètre : contacter le superviseur de pôle (isolation tenant).

---

## 7. Dépannage utilisateur

| Problème | Action |
|----------|--------|
| « Compte non approuvé » | Contacter admin — menu Enrollments |
| Mission invisible | Vérifier affectation équipe / pôle |
| Étape workflow bloquée | Voir guide workflows — validation ou prérequis |
| IA ne répond pas | Vérifier `AI_COPILOT_ENABLED` (exploitation) ; mode stub actif par défaut |

---

## 8. Voir aussi

- [Guide workflows](03-guide-workflows.md) — créer et publier un workflow
- [Guide IA](04-guide-ia-copilot.md) — copilote et gouvernance IA
- [Guide exploitation](06-guide-exploitation.md) — incidents et maintenance
