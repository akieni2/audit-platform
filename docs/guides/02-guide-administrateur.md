# Guide administrateur — IAM, départements et gouvernance

## 1. Public et accès

**Public** : super administrateurs institutionnels, administrateurs IAM, responsables de pôle avec droits `manageUsers` / `manageDepartments`.

**Accès console** : `/admin` (nécessite `can:manageUsers`).

| Rôle / permission | Capacités |
|-------------------|-----------|
| `super_admin` | Tous droits, enrollments |
| `admin` (legacy / institutionnel) | Console admin, utilisateurs |
| `manage_users` | Gestion utilisateurs |
| `manage_departments` | CRUD départements |
| `manageEnrollmentRequests` | **Super admin uniquement** |

---

## 2. Console d'administration

### 2.1 Tableau de bord admin

1. Connexion → redirection automatique si mode `technical_admin`.
2. URL : `/admin`.
3. Liens rapides : utilisateurs, sécurité, départements.

### 2.2 Gestion des utilisateurs

**Liste** : `/admin/users`

| Action | Route | Description |
|--------|-------|-------------|
| Créer | `/admin/users/create` | Nouveau compte |
| Modifier | `/admin/users/{id}/edit` | Rôle, département, statut |
| Désactiver | POST deactivate | Révoque l'accès |
| Reset MDP | POST password-reset | Email réinitialisation |
| Supprimer (soft) | DELETE | Révocation + traces conservées |

**Procédure — créer un utilisateur**

1. `/admin/users/create`.
2. Renseigner : nom, email, département, **rôle institutionnel**.
3. Définir `approval_status` → `approved` pour activer immédiatement.
4. Enregistrer → audit `user_created` dans journal sécurité.

**Procédure — modifier le rattachement IAM**

1. Éditer l'utilisateur.
2. Changer `role_id` ou `department_id`.
3. Enregistrer → événement `iam_attributes_changed` journalisé.

### 2.3 Approbation des inscriptions (enrollments)

**Réservé super admin** : `/admin/enrollments`

1. Consulter les demandes `pending`.
2. Ouvrir **Review** → vérifier identité / département.
3. **Approuver** ou **Rejeter** avec motif.
4. L'utilisateur approuvé peut se connecter (`active` middleware).

### 2.4 Gestion des départements (pôles)

**Route** : `/admin/departments`

| Champ enterprise | Usage |
|------------------|-------|
| Code | Identifiant court (ex. `DSI`) |
| Parent | Hiérarchie pôles |
| Méthodologie par défaut | Mission / workflow |
| Taxonomie par défaut | Risques |
| Profil intelligence | Analytics |

**Procédure — nouveau pôle**

1. Créer département actif.
2. Affecter superviseur (`supervisor_user_id`) si applicable.
3. Le **tenant context** est créé automatiquement au premier accès (Sprint 12).

### 2.5 Journal de sécurité IAM

**Route** : `/admin/security/audit-logs`

Événements tracés :

- Connexions / échecs / verrouillages
- Création / modification / désactivation utilisateurs
- Missions, documents, questionnaires
- Refus d'autorisation (`authorization_denied`)

Filtres recommandés : date, module, utilisateur, IP.

---

## 3. Gouvernance enterprise

### 3.1 Catalogues

| Module | URL | Admin |
|--------|-----|-------|
| Méthodologies | `/enterprise/methodologies` | Référentiels ISO, contrôles |
| Taxonomies | `/enterprise/taxonomies` | Classifications risques |
| Contrôles | `/enterprise/controls` | Bibliothèque |
| Consolidation | `/enterprise/consolidation` | Multi-départements |

### 3.2 Dashboards exécutifs

Accès : gate `viewExecutiveDashboard`

| Dashboard | URL |
|-----------|-----|
| National | `/executive/national-dashboard` |
| Comparaison pôles | `/executive/department-comparison` |
| Risk intelligence | `/executive/risk-intelligence` |
| Maturité | `/executive/maturity-index` |
| SWOT / RACI | `/executive/swot-dashboard`, `raci-dashboard` |
| Analyse organisationnelle | `/executive/organizational-analysis` |

### 3.3 Observabilité enterprise

| Page | URL | Droit |
|------|-----|-------|
| Santé plateforme | `/observability/enterprise/health` | Admin menu |
| Sécurité | `/observability/enterprise/security` | Security logs |
| Files | `/observability/enterprise/queues` | Auth |
| Performance | `/observability/enterprise/performance` | Admin |
| IA | `/observability/enterprise/ai` | Admin |
| Workflow center | `/workflows/observability` | Auth |

---

## 4. Multi-tenant (isolation départements)

### Principe

Chaque pôle dispose d'un **TenantContext** (clé `tenant_{code}`).

- Utilisateur standard : accès missions de son département (+ supervision).
- Superviseur national : scope `national`.

### Middleware

- `ResolveTenantContext` : résout le contexte à chaque requête web.
- `EnforceTenantIsolation` : bloque l'accès cross-pôle sur routes mission.

### Administration

1. Ne pas désactiver `ENTERPRISE_TENANT_ISOLATION` en production sans analyse RSSI.
2. Vérifier `tenant_contexts` en base après création d'un nouveau pôle.

---

## 5. Builders (supervision)

Les builders suivants sont accessibles depuis le menu opérationnel (policies permissives — encadrez par procédure interne) :

| Builder | URL |
|---------|-----|
| Workflow | `/workflow-builder` |
| Questionnaire | `/questionnaire-builder` |
| Formulaire | `/form-builder` |
| SWOT | `/swot-builder` |
| RACI | `/raci-builder` |

**Recommandation** : désigner des **référents méthodologie** par pôle pour publier les modèles.

---

## 6. Copilote IA (admin)

| Page | URL |
|------|-----|
| Analytics IA | `/ai/analytics` |
| Monitoring | `/observability/enterprise/ai` |

Configuration `.env` :

```env
AI_COPILOT_ENABLED=true
AI_COPILOT_DRIVER=stub
# openai | ollama | azure_openai
```

---

## 7. Checklist admin mensuelle

- [ ] Revue comptes inactifs / non approuvés
- [ ] Revue journal sécurité (échecs connexion, escalade)
- [ ] Vérification jobs échoués (`failed_jobs`)
- [ ] Vérification intégrité audit immutable
- [ ] Sauvegarde base validée (exploitation)
- [ ] Revue recommandations IA en attente validation

---

## 8. Voir aussi

- [Guide workflows](03-guide-workflows.md)
- [Manuel procédures sécurité](05-manuel-procedures-securite.md)
- [Guide exploitation](06-guide-exploitation.md)
