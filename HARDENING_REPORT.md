# HARDENING REPORT

Projet: `audit-platform`

Date: 2026-05-13

Type d'audit: audit structurel statique du depot Laravel, avec focus sur les phases 1.5 et 2.

Limite importante: ce rapport est base sur l'analyse du code, des migrations, des modeles, des controllers, des policies et des vues. La CLI `php` n'etait pas disponible dans l'environnement d'audit, donc l'execution des tests, de `artisan migrate:status` et la verification de la table runtime `migrations` n'ont pas pu etre confirmees directement.

---

## 1. Inventaire base de donnees

### 1.1 Inventaire des tables, colonnes, soft deletes, statuts et JSON

Format:
- `Table`
  - Colonnes: ...
  - Soft deletes: present / absent
  - Colonnes `status/state/active`: ...
  - JSON / metadata: ...

- `actifs`
  - Colonnes: `id`, `processus_id`, `nom`, `description`, `type`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `actions_correctives`
  - Colonnes: `id`, `risque_id`, `description`, `responsable`, `date_echeance`, `statut`, `recommendation_library_id`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `statut`
  - JSON / metadata: aucun

- `audit_logs`
  - Colonnes: `id`, `user_id`, `action`, `module`, `description`, `ip`, `user_agent`, `metadata`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: `metadata`

- `audit_plans`
  - Colonnes: `id`, `mission_id`, `titre`, `description`, `niveau`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `audit_programmes`
  - Colonnes: `id`, `audit_plan_id`, `procedure`, `type`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `cache`
  - Colonnes: `key`, `value`, `expiration`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `cache_locks`
  - Colonnes: `key`, `owner`, `expiration`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `constats`
  - Colonnes: `id`, `mission_id`, `service_id`, `description`, `cause`, `consequence`, `recommandation`, `gravite`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `controles`
  - Colonnes: `id`, `risque_id`, `description`, `type`, `efficacite`, `commentaire`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `department_audit_consolidations`
  - Colonnes: `id`, `mission_id`, `department_id`, `synthesis`, `global_risk_level`, `key_findings`, `recommendations`, `generated_by_ai`, `validated_by`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `departments`
  - Colonnes: `id`, `name`, `code`, `type`, `description`, `active`, `accent_color`, `logo_path`, `supervisor_user_id`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `active`
  - JSON / metadata: aucun

- `entretien_responses`
  - Colonnes: `id`, `entretien_id`, `questionnaire_question_id`, `answer_boolean`, `answer_text`, `answer_json`, `observation`, `uploaded_documents_metadata`, `detected_risk`, `created_by`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: `answer_json`, `uploaded_documents_metadata`

- `entretiens`
  - Colonnes: `id`, `mission_id`, `service_id`, `responsable_nom`, `role`, `chef_hierarchique`, `auditeur`, `date_entretien`, `email`, `telephone`, `notes`, `questionnaire_template_id`, `conducted_by`, `interviewed_person`, `interviewed_role`, `conducted_at`, `status`, `validation_status`, `synthesis`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `status`, `validation_status`
  - JSON / metadata: aucun

- `failed_jobs`
  - Colonnes: `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `identified_risks`
  - Colonnes: `id`, `mission_id`, `service_id`, `entretien_id`, `questionnaire_question_id`, `title`, `description`, `category`, `probability`, `impact`, `criticality`, `recommendation`, `ai_generated`, `validated_by_human`, `created_by`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `job_batches`
  - Colonnes: `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `jobs`
  - Colonnes: `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `mission_documents`
  - Colonnes: `id`, `mission_id`, `service_id`, `entretien_id`, `uploaded_by`, `filename`, `original_name`, `mime_type`, `disk`, `path`, `size`, `category`, `description`, `version`, `metadata`, `created_at`, `updated_at`, `deleted_at`
  - Soft deletes: present
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: `metadata`

- `mission_raci_previews`
  - Colonnes: `id`, `mission_id`, `service_id`, `process_label`, `status`, `metadata`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `status`
  - JSON / metadata: `metadata`

- `mission_swot_previews`
  - Colonnes: `id`, `mission_id`, `service_id`, `status`, `metadata`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `status`
  - JSON / metadata: `metadata`

- `mission_team_members`
  - Colonnes: `id`, `mission_id`, `user_id`, `mission_role`, `designation`, `is_lead`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `mission_workflow_events`
  - Colonnes: `id`, `mission_id`, `user_id`, `action`, `from_status`, `to_status`, `comment`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `from_status`, `to_status`
  - JSON / metadata: aucun

- `missions`
  - Colonnes: `id`, `organisation`, `reference`, `objet`, `description`, `periode_audit`, `ordre_mission_reference`, `date_ordre_mission`, `observations_generales`, `date_debut`, `date_fin`, `deadline`, `auditeur_id`, `department_id`, `mission_type`, `mission_status`, `priority`, `sensitivity_level`, `confidentiality_level`, `supervising_department_id`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `mission_status`
  - JSON / metadata: aucun

- `notifications`
  - Colonnes: `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `password_reset_tokens`
  - Colonnes: `email`, `token`, `created_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `permission_role`
  - Colonnes: `id`, `role_id`, `permission_id`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `permissions`
  - Colonnes: `id`, `slug`, `name`, `group`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `personal_access_tokens`
  - Colonnes: `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `processus`
  - Colonnes: `id`, `mission_id`, `nom`, `description`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `questionnaire_questions`
  - Colonnes: `id`, `questionnaire_section_id`, `code`, `question`, `help_text`, `question_type`, `required`, `allows_observation`, `allows_risk_detection`, `expected_documents`, `risk_category`, `risk_level`, `sort_order`, `active`, `metadata`, `created_at`, `updated_at`, `deleted_at`
  - Soft deletes: present
  - Colonnes `status/state/active`: `active`
  - JSON / metadata: `metadata`

- `questionnaire_sections`
  - Colonnes: `id`, `questionnaire_template_id`, `title`, `description`, `sort_order`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `questionnaire_templates`
  - Colonnes: `id`, `name`, `slug`, `description`, `mission_type`, `department_scope`, `active`, `version`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`
  - Soft deletes: present
  - Colonnes `status/state/active`: `active`
  - JSON / metadata: `department_scope`

- `questionnaires`
  - Colonnes: `id`, `entretien_id`, `titre`, `description`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `questions`
  - Colonnes: `id`, `questionnaire_id`, `question`, `type`, `probabilite`, `impact`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `recommendation_libraries`
  - Colonnes: `id`, `titre`, `description`, `categorie`, `type_controle`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `reponses`
  - Colonnes: `id`, `entretien_id`, `question_id`, `reponse`, `commentaire`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `risk_libraries`
  - Colonnes: `id`, `titre`, `description`, `processus`, `categorie`, `probabilite_default`, `impact_default`, `risk_library_id`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `risques`
  - Colonnes: `id`, `actif_id`, `description`, `niveau`, `proprietaire`, `departement`, `date_revue`, `plan_mitigation`, `statut_risque`, `impact_inherent`, `probabilite_inherent`, `criticite_inherent`, `score_inherent`, `impact_residuel`, `probabilite_residuel`, `criticite_residuel`, `score_residuel`, `severity`, `owner_department_id`, `source_department_id`, `target_department_id`, `shared`, `cross_department`, `escalated`, `treatment_plan`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `statut_risque`
  - JSON / metadata: aucun

- `roles`
  - Colonnes: `id`, `slug`, `name`, `description`, `hierarchy_level`, `active`, `created_at`, `updated_at`
  - Soft deletes: absent
  - Colonnes `status/state/active`: `active`
  - JSON / metadata: aucun

- `services`
  - Colonnes: `id`, `mission_id`, `code`, `nom`, `responsable`, `description`, `chef_service_user_id`, `chef_service_nom`, `chef_service_fonction`, `chef_service_email`, `chef_service_telephone`, `service_type`, `service_scope`, `active`, `observations`, `audit_priority`, `risk_level`, `audit_status`, `metadata`, `created_at`, `updated_at`, `deleted_at`
  - Soft deletes: present
  - Colonnes `status/state/active`: `active`, `audit_status`
  - JSON / metadata: `metadata`

- `sessions`
  - Colonnes: `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`
  - Soft deletes: absent
  - Colonnes `status/state/active`: aucune
  - JSON / metadata: aucun

- `users`
  - Colonnes: `id`, `name`, `prenom`, `email`, `email_verified_at`, `password`, `remember_token`, `role`, `department_id`, `role_id`, `active`, `position`, `telephone`, `phone`, `matricule`, `date_naissance`, `fonction`, `last_login_at`, `failed_login_attempts`, `locked_until`, `mfa_enabled`, `mfa_recovery_codes`, `profile_photo`, `approval_status`, `approved_at`, `approved_by`, `registration_requested_department_id`, `password_changed_at`, `must_change_password`, `password_expires_at`, `deleted_at`, `deleted_by`, `created_at`, `updated_at`
  - Soft deletes: present
  - Colonnes `status/state/active`: `active`, `approval_status`
  - JSON / metadata: aucun

### 1.2 Tables manquantes referencees dans le code

#### CRITICAL
- `mission_services` est implicitement referencee par Eloquent via `App\Models\MissionService`, parce que ce modele n'ecrase pas `$table`. Par convention, Eloquent visera `mission_services`, alors que seule la table `services` existe.

#### MINOR
- Aucune autre table explicitement referencee via `DB::table(...)`, `protected $table`, `exists:` ou relations n'apparait manquante dans les migrations.

### 1.3 Colonnes referencees mais inexistantes

#### MAJOR
- `reponses.observation` est referencee par `App\Models\Reponse`, alors que la colonne migree est `commentaire`.

#### MINOR
- Le pivot `permission_role` est utilise avec `withTimestamps()` dans `App\Models\Permission`, mais la table ne contient pas `created_at` / `updated_at`.

### 1.4 Observations base de donnees

- La plateforme contient simultanement un ancien systeme de questionnaires (`questionnaires`, `questions`, `reponses`) et un nouveau systeme (`questionnaire_templates`, `questionnaire_sections`, `questionnaire_questions`, `entretien_responses`). Ce double systeme est acceptable en transition, mais augmente fortement le risque de derive fonctionnelle.

---

## 2. Audit des modeles Eloquent

### 2.1 Resume global

- Nombre de modeles trouves dans `app/Models`: 31
- Modeles avec `SoftDeletes` coherents avec le schema:
  - `User`
  - `Service`
  - `MissionDocument`
  - `QuestionnaireTemplate`
  - `QuestionnaireQuestion`
- Modeles a risque structurel fort:
  - `MissionService`
  - `Mission`
  - `Reponse`
  - `QuestionnaireSection`

### 2.2 Audit modele par modele

- `ActionCorrective`
  - Table reelle: oui (`actions_correctives`)
  - Relations: plausibles
  - Casts: incomplets (`date_echeance` non caste)
  - SoftDeletes: absent / coherent
  - Fillable: non audite en detail ici
  - Niveau: MINOR

- `Actif`
  - Table reelle: oui (`actifs`)
  - Relations: plausibles
  - SoftDeletes: absent / coherent
  - Policy dediee: absente
  - Niveau: MAJOR (gouvernance d'ecriture, pas schema)

- `AuditLog`
  - Table reelle: oui (`audit_logs`)
  - Relations: plausibles
  - Policy dediee: non necessaire si acces gere par gate admin
  - Niveau: OK structurel

- `Constat`
  - Table reelle: oui (`constats`)
  - Relations: non verifiees exhaustivement
  - Niveau: MINOR

- `Controle`
  - Table reelle: oui (`controles`)
  - Relations: plausibles
  - Policy dediee: absente
  - Niveau: MAJOR

- `Department`
  - Table reelle: oui (`departments`)
  - Policy: presente et enregistree
  - Niveau: OK structurel

- `DepartmentAuditConsolidation`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: `generated_by_ai` coherent
  - Policy: presente et enregistree
  - Niveau: OK structurel

- `Entretien`
  - Table reelle: oui
  - Relations: globalement coherentes
  - Casts: `conducted_at`, `date_entretien` coherents
  - SoftDeletes: absent / coherent
  - Fillable: large mais coherent avec le schema
  - Risque: `questionnaireCompletionPercent()` compte toutes les reponses, y compris celles de questions potentiellement inactives
  - Niveau: MINOR

- `EntretienResponse`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: `answer_json`, `uploaded_documents_metadata` coherents
  - Policy dediee: absente, mais l'acces est actuellement medie par `EntretienPolicy`
  - Niveau: MINOR

- `IdentifiedRisk`
  - Table reelle: oui
  - Relations: coherentes
  - Policy: presente et enregistree
  - Niveau: OK structurel

- `Mission`
  - Table reelle: oui
  - Relations: majoritairement coherentes
  - Casts: dates coherentes
  - SoftDeletes: absent / coherent
  - Probleme critique: relation `auditPlans()` vers `AuditPlan::class` sans modele present, et methode `genererPlanAuditAutomatique()` appelle aussi `AuditProgramme::create()` sans modele present
  - Default applicatif `mission_status = brouillon`, mais default DB encore derive de `draft`
  - Niveau: CRITICAL

- `MissionDocument`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: coherents
  - SoftDeletes: present / coherent
  - Policy: presente et enregistree
  - Niveau: OK structurel

- `MissionRaciPreview`
  - Table reelle: oui
  - Relations: coherentes
  - Policy dediee: absente, acceptable tant qu'aucune route d'ecriture n'est exposee
  - Niveau: MINOR

- `MissionService`
  - Table reelle: non, car le modele n'ecrase pas `$table` vers `services`
  - Relations: heritees de `Service`
  - SoftDeletes: herites via `Service`
  - Probleme critique: Eloquent resolvra `mission_services`, table inexistante
  - Probleme critique additionnel: incoherence avec le route binding qui retourne un `Service`
  - Niveau: CRITICAL

- `MissionSwotPreview`
  - Table reelle: oui
  - Relations: coherentes
  - Policy dediee: absente, acceptable tant qu'aucune route d'ecriture n'est exposee
  - Niveau: MINOR

- `MissionTeamMember`
  - Table reelle: oui
  - Relations: plausibles
  - Policy dediee: absente, acces actuellement porte par `MissionPolicy`
  - Niveau: MINOR

- `MissionWorkflowEvent`
  - Table reelle: oui
  - Relations: plausibles
  - Policy dediee: absente, acceptable si lecture toujours via mission
  - Niveau: MINOR

- `Permission`
  - Table reelle: oui
  - Relations: pivot incoherent avec `withTimestamps()`
  - Niveau: MAJOR

- `Processus`
  - Table reelle: oui
  - Relations: plausibles
  - Policy dediee: absente
  - Niveau: MAJOR

- `Question`
  - Table reelle: oui (`questions`)
  - Legacy model
  - Niveau: MINOR

- `Questionnaire`
  - Table reelle: oui (`questionnaires`)
  - Legacy model incomplet par rapport au schema (`entretien_id`, `description` sous-modele)
  - Niveau: MINOR

- `QuestionnaireQuestion`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: coherents
  - SoftDeletes: presents
  - Risque: l'archivage est contredit par la suppression dure des sections parentes
  - Niveau: MAJOR

- `QuestionnaireSection`
  - Table reelle: oui
  - Relations: coherentes
  - SoftDeletes: absents
  - Risque: suppression dure alors que les questions enfants sont soft deletables et peuvent deja etre referencees par `entretien_responses`
  - Niveau: MAJOR

- `QuestionnaireTemplate`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: coherents
  - SoftDeletes: presents
  - Probleme fonctionnel fort: policy completement ouverte
  - Niveau: CRITICAL

- `RecommendationLibrary`
  - Table reelle: oui
  - Niveau: MINOR

- `Reponse`
  - Table reelle: oui (`reponses`)
  - Fillable incoherent (`observation` au lieu de `commentaire`)
  - Niveau: MAJOR

- `Risque`
  - Table reelle: oui
  - Policy: presente et enregistree
  - Risque majeur: creation API mal protegee
  - Niveau: MAJOR

- `RiskLibrary`
  - Table reelle: oui
  - Niveau: MINOR

- `Role`
  - Table reelle: oui
  - Niveau: OK structurel

- `Service`
  - Table reelle: oui
  - Relations: coherentes
  - Casts: coherents
  - SoftDeletes: presents
  - Risque majeur: `chef_service_user_id` trop permissif dans les Form Requests
  - Niveau: MAJOR

- `User`
  - Table reelle: oui
  - Relations / casts / soft deletes: globalement coherents
  - Niveau: OK structurel, mais schema `users` souffre de migrations en double

### 2.3 Accessors / mutators

- `Service::responsableDisplay()` est coherent, mais ne couvre pas le cas ou `chef_service_user_id` est renseigne sans relation chargee.
- `Entretien::questionnaireCompletionPercent()` peut surevaluer la progression si des reponses subsistent pour des questions desactivees ou archivees.

---

## 3. Audit des controllers

### 3.1 Requetes sur colonnes inexistantes

- Aucune requete critique sur colonne inexistante n'a ete confirmee dans les controllers phases 1.5 et 2.
- Les principaux ecarts detectes portent plutot sur:
  - mismatch modele / table
  - mismatch policy / metier
  - duplication fonctionnelle
  - route binding incoherent

### 3.2 Findings controllers

#### CRITICAL
- `ServiceController` utilise `MissionService::query()` alors que `MissionService` pointe implicitement vers la table inexistante `mission_services`.
- `MissionDocumentController` type-hinte `MissionService $service`, alors que `AppServiceProvider` bind le parametre `service` avec `Service::query()`. Le binding retourne donc un `Service`, pas un `MissionService`.
- `Api\V1\RiskController::store()` cree un `Risque` a partir de n'importe quel `actif_id` valide, sans verifier la visibilite metier du parent. `StoreRisqueRequest::authorize()` accepte tout utilisateur authentifie.

#### MAJOR
- `EntretienConduiteController::storeResponses()` fait `updateOrCreate()` sur `EntretienResponse`, mais fait toujours `IdentifiedRisk::create()` pour le bloc `identified_risk`. Une sauvegarde progressive produit donc des doublons de risques.
- `DashboardController` affiche une "Repartition des risques par service" calculee uniquement sur `mission_id`. Toutes les lignes d'un meme service de mission peuvent donc recevoir le meme total.
- `QuestionnaireTemplateController::destroySection()` supprime durement la section, alors que des `entretien_responses` peuvent deja referencer des questions enfants via une FK `restrictOnDelete`.

#### MINOR
- `MissionController` ajoute des KPI utiles, mais leur robustesse depend de la coherence des statuts `Entretien`.
- Les controllers legacy (`ProcessusController`, `ActifController`, `ControleController`, `ActionCorrectiveController`) reposent surtout sur la visibilite parent, sans policy dediee par ressource.

### 3.3 Eager loading invalides ou fragiles

#### CRITICAL
- `ServiceController` eager-load `entretiens.questionnaireTemplate.sections.questions` via `MissionService::query()`. Si `MissionService` ne pointe pas vers `services`, toute la chaine casse avant l'eager loading.

#### MINOR
- Aucun eager load manifestement indefini n'a ete confirme ailleurs dans les controllers phases 1.5 / 2.

### 3.4 Dependances circulaires / fortes

- Pas de dependance circulaire PHP explicite confirmee.
- Dependances fortes a surveiller:
  - `Mission` -> `genererPlanAuditAutomatique()` -> classes absentes `AuditPlan` / `AuditProgramme`
  - `DashboardController` -> logique de synthese directement en controller + metric globale dans Blade

---

## 4. Audit des migrations

### 4.1 Migrations manquantes

#### CRITICAL
- Pas de migration pour une table `mission_services`, alors que `MissionService` la reference implicitement par convention Eloquent.

### 4.2 Migrations jamais executees

- Non determinable statiquement depuis le depot seul.
- Pour verifier proprement il faut comparer les fichiers de `database/migrations` avec la table runtime `migrations`.

### 4.3 Doublons et conflits

#### CRITICAL
- `2026_03_05_153839_add_security_fields_to_users_table.php` ajoute `prenom` et `fonction`.
- `2026_03_05_174934_add_user_identity_fields.php` re-ajoute `prenom` et `fonction` sans garde `Schema::hasColumn(...)`.
- Sur une installation propre, cela est un blocker potentiel.

#### CRITICAL
- `2026_05_09_140000_normalize_mission_workflow_statuses.php` met a jour `missions.mission_status` avant que cette colonne n'existe dans la chronologie des migrations.

#### MAJOR
- `2026_05_10_100004_add_institutional_fields_to_missions_table.php` garde un default DB oriente legacy (`draft`) alors que le domaine applicatif travaille en `brouillon`.

#### MAJOR
- `2026_05_16_100000_phase2_enrich_services_table.php` et `2026_05_16_100001_phase2_enrich_entretiens_table.php` utilisent des `if (! Schema::hasColumn(...))` dans `up()`, mais leurs `down()` suppriment les colonnes de facon large. Une rollback peut retirer des colonnes preexistantes dans certains contextes.

### 4.4 Tables partiellement creees / derivees

#### MAJOR
- Les tables legacy des questionnaires coexistent avec les tables phase 1.5, sans couche d'abstraction forte ni plan clair de decommission.

#### MAJOR
- `questionnaire_questions` est soft deletable, mais `questionnaire_sections` ne l'est pas et peut hard-delete l'arbre.

### 4.5 Hardening migration recommande

1. Corriger l'ordre et/ou la garde de `normalize_mission_workflow_statuses`.
2. Fusionner ou rendre idempotentes les migrations `users` qui doublonnent.
3. Fixer explicitement le default DB de `mission_status`.
4. Normaliser les `down()` des migrations phase 2.

---

## 5. Audit des policies

### 5.1 Models sans policy dediee

Models trouves sans policy explicite:
- `ActionCorrective`
- `Actif`
- `AuditLog`
- `Constat`
- `Controle`
- `EntretienResponse`
- `MissionRaciPreview`
- `MissionSwotPreview`
- `MissionTeamMember`
- `MissionWorkflowEvent`
- `Permission`
- `Processus`
- `Question`
- `Questionnaire`
- `QuestionnaireQuestion`
- `QuestionnaireSection`
- `RecommendationLibrary`
- `Reponse`
- `RiskLibrary`
- `Role`

Observation:
- tous n'ont pas necessairement besoin d'une policy dediee aujourd'hui;
- en revanche, pour des ressources avec ecriture directe, l'absence de policy devient un risque.

### 5.2 Policies presentes mais non enregistrees

- Aucune policy trouvee dans `app/Policies` n'apparait orpheline.
- Les 10 policies presentes sont enregistrees dans `AppServiceProvider`.

### 5.3 Capacites incoherentes

#### CRITICAL
- `QuestionnaireTemplatePolicy` retourne `true` pour `view`, `create`, `update`, `delete`, `duplicate`.
- Cela contredit directement `User::canManageQuestionnaireLibrary()` et ouvre l'administration de la bibliotheque a tout utilisateur authentifie.

#### MAJOR
- `MissionDocumentPolicy` et `ServicePolicy` sont bien presentes, mais la qualite de la securisation reste partiellement affaiblie par les validations trop permissives des Form Requests (`chef_service_user_id`).

#### MINOR
- Plusieurs modeles d'ecriture continuent d'etre proteges seulement via les policies du parent (`Mission`, `Entretien`, `Risque`) plutot que via leur propre policy.

---

## 6. Audit des vues Blade

### 6.1 Relations null dangereuses

#### MAJOR
- `resources/views/entretiens/index.blade.php` affiche l'action "Conduite" des qu'un `questionnaire_template_id` est present. Si le template a ete soft-delete ou inactivate, l'UI affiche encore un lien qui mene a un `404`.

### 6.2 Acces a colonnes inexistantes

- Aucun acces Blade critique a une colonne inexistante n'a ete confirme sur les vues phases 1.5 / 2.

### 6.3 Erreurs syntaxiques

- Aucune erreur syntaxique Blade evidente n'a ete confirmee dans les vues inspectees.

### 6.4 Encodage UTF-8 casse

#### MINOR
- `resources/views/services/index.blade.php` contient du texte corrompu:
  - `Services audits`
  - `primtre daudit`
  - `Crer le service`
  - `Rapport service  bientt`
- `routes/web.php` contient aussi des commentaires corrompus.

### 6.5 Vues stale / legacy

#### MINOR
- `resources/views/layouts/sidebar.blade.php` garde encore un lien legacy vers `module.questionnaires`, alors que la navigation principale moderne passe par `questionnaire-templates.*`.
- Cela entretient le double systeme legacy / phase 1.5.

### 6.6 Dashboard data correctness

#### MAJOR
- `resources/views/dashboard.blade.php` execute directement `User::query()->where('active', true)->count()` dans la vue. Cela fuit un KPI global et melange presentation et data access.

#### MAJOR
- Le graphe "Repartition des risques par service" repose sur des donnees preparees incorrectement par le controller.

---

## 7. Normalisation recommandee

### 7.1 `status`

Standard recommande:
- toujours une colonne `status` pour l'etat principal d'une ressource.
- valeurs enumerees en constantes de modele.
- default DB et default modele obligatoirement alignes.

Exemples:
- `missions.status` (ou garder `mission_status` mais figer la convention)
- `entretiens.status`
- `services.audit_status`

### 7.2 `active`

Standard recommande:
- utiliser `active BOOLEAN NOT NULL DEFAULT true` pour les ressources activables;
- pas de variantes `enabled`, `is_active`, `visible` si non necessaire;
- documenter clairement si `active=false` masque l'entite ou interdit son usage.

### 7.3 `deleted_at`

Standard recommande:
- utiliser `deleted_at` uniquement si le modele utilise `SoftDeletes`;
- eviter les suppressions dures sur les parents quand les enfants sont soft deletables;
- privilegier l'archivage sur les referentiels (`questionnaire_templates`, `questionnaire_questions`, `services`, `mission_documents`).

### 7.4 `validation_status`

Standard recommande:
- reserver `validation_status` aux workflows de validation humaine distincts du cycle de vie principal;
- valeurs conseillees: `pending`, `validated`, `rejected`, `returned`;
- ne pas l'utiliser a la place de `status`.

### 7.5 `metadata` JSON

Standard recommande:
- `metadata` uniquement pour les extensions non structurantes;
- ne pas stocker des champs metier majeurs dans `metadata`;
- documenter les cles admises par ressource;
- preferer des colonnes reelles pour:
  - statut
  - identifiants
  - proprietaires
  - niveaux de risque
  - dates de workflow

### 7.6 Conventions de nommage

Standard recommande:
- modeles singuliers: `MissionDocument`, `QuestionnaireTemplate`
- tables plurielles snake_case: `mission_documents`, `questionnaire_templates`
- FK en `_id`
- routes ressourcees scopees par parent quand metierement necessaire:
  - `missions/{mission}/services/{service}`
- eviter les alias de modele sans `$table` explicite si l'on herite d'une table legacy:
  - `MissionService` doit soit definir `protected $table = 'services';`, soit disparaitre au profit de `Service`.

---

## 8. Classification des erreurs

### CRITICAL

1. `MissionService` vise implicitement `mission_services`, table inexistante.
2. Route binding `service` retourne `Service`, alors que les controllers phase 2 attendent `MissionService`.
3. `Mission` depend de `AuditPlan` / `AuditProgramme` sans modeles presents.
4. `QuestionnaireTemplatePolicy` ouvre la bibliotheque a tous les utilisateurs authentifies.
5. `Api\V1\RiskController::store()` bypass les contraintes de visibilite metier.
6. Migration `normalize_mission_workflow_statuses` execute des updates avant existence confirmee de `mission_status`.
7. Doublon de migrations `users` sur `prenom` / `fonction`.

### MAJOR

1. `Reponse` mappe `observation` alors que la colonne reelle est `commentaire`.
2. `Permission::roles()->withTimestamps()` sans timestamps sur `permission_role`.
3. `QuestionnaireSection` hard-delete des enfants `QuestionnaireQuestion` soft deletables et potentiellement references.
4. `EntretienConduiteController` duplique les `identified_risks` sur sauvegardes progressives.
5. Dashboard: comptage des risques par service incorrect.
6. Dashboard Blade: KPI global `usersActive` calcule dans la vue.
7. `chef_service_user_id` accepte n'importe quel `users.id` existant, sans contrainte de perimetre mission.
8. Default DB `missions.mission_status` incoherent avec le domaine.
9. Plusieurs ressources d'ecriture n'ont pas de policy dediee (`Processus`, `Actif`, `Controle`, `ActionCorrective`).

### MINOR

1. Mojibake / encodage casse dans `services/index` et certains commentaires.
2. Lien legacy `module.questionnaires` encore present.
3. `Entretien::questionnaireCompletionPercent()` peut surevaluer la progression.
4. `ActionCorrective` manque d'un cast date explicite.
5. `Questionnaire` legacy sous-modele par rapport a son schema.
6. Verification "migrations jamais executees" impossible statiquement sans base runtime.

---

## 9. Plan de correction priorise

### Etape 1 - Remettre le schema et le bootstrap en etat de migration propre

1. Corriger `MissionService`:
   - soit ajouter `protected $table = 'services';`
   - soit supprimer l'alias et revenir a `Service` partout.
2. Corriger `AppServiceProvider`:
   - binder `service` en retournant le meme type que celui attendu par les controllers;
   - idealement unifier sur `Service`.
3. Corriger les migrations `users` en doublon:
   - fusionner ou rendre idempotente `2026_03_05_174934_add_user_identity_fields.php`.
4. Corriger `2026_05_09_140000_normalize_mission_workflow_statuses.php`:
   - soit la deplacer apres l'ajout de `mission_status`,
   - soit la guarder par `Schema::hasColumn('missions', 'mission_status')`.
5. Aligner le default DB de `missions.mission_status` sur `brouillon`.

### Etape 2 - Fermer les failles de securite

6. Reparer `QuestionnaireTemplatePolicy` pour reutiliser `User::canManageQuestionnaireLibrary()` et le scope departemental.
7. Restreindre `StoreRisqueRequest` / `Api\V1\RiskController::store()` avec une verification de visibilite reelle sur `actif_id`.
8. Durcir `chef_service_user_id`:
   - n'accepter que les utilisateurs eligibles a la mission ou du bon departement / perimetre.

### Etape 3 - Stabiliser les workflows phase 1.5 / 2

9. Corriger la suppression des sections/questionnaires:
   - interdiction si reponses existantes, ou archivage uniquement.
10. Empotentiser la creation des `identified_risks` en conduite:
   - `updateOrCreate()` ou deduplication par `entretien_id + questionnaire_question_id + title`.
11. Corriger la resolution des templates soft-deletes dans les entretiens:
   - soit `withTrashed()` cote relation,
   - soit masquer l'action "Conduite" si template indisponible,
   - soit interdire le soft delete d'un template encore lie.

### Etape 4 - Corriger la dette de coherence applicative

12. Corriger `Reponse` pour utiliser `commentaire`.
13. Ajouter timestamps au pivot `permission_role` ou retirer `withTimestamps()`.
14. Ajouter les modeles manquants `AuditPlan` / `AuditProgramme` ou neutraliser la logique tant que non prete.
15. Ajouter les casts manquants (`ActionCorrective::date_echeance`, autres dates legacy utiles).

### Etape 5 - Corriger les couches presentation et pilotage

16. Corriger le dashboard:
   - comptage de risques reellement par service;
   - suppression du `User::query()` dans Blade;
   - toute la data doit venir du controller / service.
17. Corriger l'encodage des fichiers mojibake.
18. Nettoyer les liens legacy questionnaires si la bibliotheque phase 1.5 devient la seule source officielle.

### Etape 6 - Hardening gouvernance et conventions

19. Ajouter des policies dediees aux ressources d'ecriture encore non couvrettes:
   - `Processus`
   - `Actif`
   - `Controle`
   - `ActionCorrective`
20. Normaliser tous les `status`, `active`, `deleted_at`, `validation_status`, `metadata`.
21. Documenter officiellement la coexistence ou la deprecation des tables legacy questionnaires.

---

## Conclusion

Les phases 1.5 et 2 ont apporte une vraie valeur metier:
- questionnaires dynamiques
- entretiens structures
- services audites
- documents de mission
- consolidation

Mais elles ont aussi introduit plusieurs incoherences structurelles majeures, dont certaines sont des blockers de robustesse:
- alias `MissionService` non finalise
- policy questionnaires ouverte
- migrations `users` en doublon
- logique `Mission` referenceant des modeles absents
- creation API de risques insuffisamment protegee

Priorite absolue:
1. schema et migrations,
2. policies et securite,
3. stabilisation des workflows phase 1.5 / 2,
4. nettoyage dashboard / vues / legacy.

