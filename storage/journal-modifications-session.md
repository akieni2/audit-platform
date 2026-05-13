# Journal detaille des modifications de la session

Projet: `audit-platform`

Branche de destination: `main`

Perimetre: ce journal couvre uniquement les commits que nous avons effectivement crees et pousses pendant cette session:

- `ac9bc06` - Phase 1.5 questionnaires dynamiques
- `9b27832` - Phase 2 services, entretiens structures, documents, consolidation
- `ca21d0d` - correctif bootstrap Horizon

---

## Commit `ac9bc06`

Message: `feat(questionnaires): bibliothèque dynamique, conduite entretien et risques identifiés`

Objet: mise en place de la bibliotheque de questionnaires institutionnels, des entretiens dynamiques relies aux services, du stockage relationnel des reponses et de la premiere couche de risques identifies.

### Fichiers modifies

- `app/Http/Controllers/EntretienConduiteController.php`
  Affichage de la conduite dynamique d'entretien, chargement du template, sauvegarde des reponses ligne par ligne et creation conditionnelle de risques identifies.

- `app/Http/Controllers/EntretienController.php`
  Ajout du support des entretiens dynamiques: choix optionnel de template, rattachement d'un questionnaire a un entretien et adaptation de la liste des entretiens.

- `app/Http/Controllers/IdentifiedRiskController.php`
  Ajout d'une action de validation humaine des risques identifies.

- `app/Http/Controllers/Questionnaires/QuestionnaireTemplateController.php`
  Creation du CRUD complet des templates, duplication, gestion des sections et des questions dynamiques.

- `app/Http/Requests/Questionnaires/StoreEntretienDynamicResponsesRequest.php`
  Validation des reponses d'entretien structurees, du stockage JSON partiel et du bloc de risque structure.

- `app/Http/Requests/Questionnaires/StoreQuestionnaireQuestionRequest.php`
  Validation des questions dynamiques et des types de question supportes.

- `app/Http/Requests/Questionnaires/StoreQuestionnaireSectionRequest.php`
  Validation de creation des sections de questionnaire.

- `app/Http/Requests/Questionnaires/StoreQuestionnaireTemplateRequest.php`
  Validation de creation des templates, generation du slug et normalisation du flag `active`.

- `app/Http/Requests/Questionnaires/UpdateQuestionnaireTemplateRequest.php`
  Validation de mise a jour des templates et correction de la gestion du champ `active`.

- `app/Models/Entretien.php`
  Extension du modele existant pour y rattacher un template, les reponses dynamiques et les risques identifies.

- `app/Models/EntretienResponse.php`
  Nouveau modele pour stocker chaque reponse d'entretien comme enregistrement requetable.

- `app/Models/IdentifiedRisk.php`
  Nouveau modele pour porter les risques detectes a partir des reponses.

- `app/Models/QuestionnaireQuestion.php`
  Nouveau modele de question dynamique avec types supportes, metadata, niveau de risque et activation.

- `app/Models/QuestionnaireSection.php`
  Nouveau modele pour structurer les questionnaires en sections ordonnees.

- `app/Models/QuestionnaireTemplate.php`
  Nouveau modele de template avec versioning, visibilite departementale et referentiel reutilisable.

- `app/Models/User.php`
  Ajout d'un helper de gouvernance pour savoir si l'utilisateur peut administrer la bibliotheque des questionnaires.

- `app/Policies/EntretienPolicy.php`
  Ajout des regles d'acces pour conduire un questionnaire dynamique et rattacher un template.

- `app/Policies/IdentifiedRiskPolicy.php`
  Ajout des regles d'acces pour la validation humaine des risques identifies.

- `app/Policies/QuestionnaireTemplatePolicy.php`
  Regles completes de consultation, creation, mise a jour, suppression et duplication des templates selon le perimetre IAM.

- `app/Providers/AppServiceProvider.php`
  Enregistrement des nouvelles policies et binding route-model pour `entretien` avec isolation des donnees.

- `app/Services/Iam/SecurityAuditService.php`
  Ajout des evenements de journalisation: creation/mise a jour de template, creation de reponse d'entretien, creation et validation de risque.

- `database/migrations/2026_05_15_140000_create_questionnaire_templates_table.php`
  Creation de la table des templates de questionnaire avec versioning et soft deletes.

- `database/migrations/2026_05_15_140001_create_questionnaire_sections_table.php`
  Creation de la table des sections de questionnaire.

- `database/migrations/2026_05_15_140002_create_questionnaire_questions_table.php`
  Creation de la table des questions dynamiques avec metadata et soft deletes.

- `database/migrations/2026_05_15_140003_add_questionnaire_template_id_to_entretiens_table.php`
  Ajout de la cle de liaison entre entretien et template de questionnaire.

- `database/migrations/2026_05_15_140004_create_entretien_responses_table.php`
  Creation du stockage relationnel des reponses d'entretien.

- `database/migrations/2026_05_15_140005_create_identified_risks_table.php`
  Creation de la table des risques identifies relies a mission, service, entretien et question.

- `resources/views/entretiens/conduite.blade.php`
  Nouvelle interface DGCPT de conduite d'entretien dynamique par sections et types de question.

- `resources/views/entretiens/index.blade.php`
  Refonte DGCPT de la creation et de la liste des entretiens avec support des templates.

- `resources/views/layouts/partials/sidebar-navigation.blade.php`
  Ajout de l'entree de navigation vers la bibliotheque de questionnaires.

- `resources/views/questionnaires/templates/create.blade.php`
  Ecran de creation de template.

- `resources/views/questionnaires/templates/edit.blade.php`
  Ecran de gestion detaillee d'un template, de ses sections et de ses questions.

- `resources/views/questionnaires/templates/index.blade.php`
  Ecran de liste de la bibliotheque des questionnaires.

- `routes/web.php`
  Declaration des routes de templates, sections, questions, conduite, reponses dynamiques, rattachement de template et validation des risques.

- `tests/Feature/Questionnaires/QuestionnaireDynamicFlowTest.php`
  Test de bout en bout du flux template -> section -> question -> entretien -> reponse -> risque.

### Resultat metier

- questionnaires desormais geres comme referentiels dynamiques;
- reponses stockees de facon relationnelle et exploitable;
- socle pose pour detection de risques, consolidation et IA.

---

## Commit `9b27832`

Message: `feat(missions): Phase 2 services audités, documents, consolidation et entretiens structurés`

Objet: structuration operationnelle des missions autour des services audites, des entretiens enrichis, du porte-documents par service et de la consolidation departementale.

### Fichiers modifies

- `app/Http/Controllers/DepartmentAuditConsolidationController.php`
  Nouveau controleur de creation d'une consolidation departementale a partir des donnees de mission.

- `app/Http/Controllers/EntretienConduiteController.php`
  Passage des entretiens en `in_progress`, calcul de progression et journalisation du demarrage lors de la conduite dynamique.

- `app/Http/Controllers/EntretienController.php`
  Enrichissement des entretiens avec statuts, metadonnees de conduite et action de completion.

- `app/Http/Controllers/MissionController.php`
  Ajout des KPI de mission (services, entretiens, risques, documents, progression) dans la fiche mission.

- `app/Http/Controllers/MissionDocumentController.php`
  Nouveau controleur pour lister, televerser et supprimer les documents rattaches a un service de mission.

- `app/Http/Controllers/ServiceController.php`
  Refonte du controleur pour gerer les services audites de facon mission-scopee, avec index, creation, edition, mise a jour et archivage.

- `app/Http/Requests/Services/StoreDepartmentAuditConsolidationRequest.php`
  Validation de creation des consolidations departementales.

- `app/Http/Requests/Services/StoreMissionDocumentRequest.php`
  Validation des uploads documentaires (types, taille, description, categorie).

- `app/Http/Requests/Services/StoreMissionServiceRequest.php`
  Validation de creation d'un service audite.

- `app/Http/Requests/Services/UpdateMissionServiceRequest.php`
  Validation de mise a jour d'un service audite.

- `app/Models/DepartmentAuditConsolidation.php`
  Nouveau modele de synthese departementale.

- `app/Models/Entretien.php`
  Enrichissement du modele avec statuts, conducteur, interviewe, date de conduite et calcul de progression.

- `app/Models/Mission.php`
  Ajout des relations vers consolidations, documents et placeholders SWOT/RACI.

- `app/Models/MissionDocument.php`
  Nouveau modele de documents de mission relies au service et a l'entretien.

- `app/Models/MissionRaciPreview.php`
  Placeholder technique pour preparer la future matrice RACI.

- `app/Models/MissionService.php`
  Alias metier de `Service` pour introduire progressivement le concept de service audite.

- `app/Models/MissionSwotPreview.php`
  Placeholder technique pour preparer la future structure SWOT.

- `app/Models/Service.php`
  Enrichissement du modele existant: responsable IAM ou libre, niveau de risque, priorite, statut audit, metadata, soft deletes, relations documents/risques/entretiens.

- `app/Policies/DepartmentAuditConsolidationPolicy.php`
  Regles d'acces pour creer et consulter les consolidations.

- `app/Policies/EntretienPolicy.php`
  Ajout de la capacite a completer un entretien.

- `app/Policies/MissionDocumentPolicy.php`
  Regles d'acces pour les documents de mission.

- `app/Policies/MissionPolicy.php`
  Ajout de `manageServices` pour distinguer la gouvernance des services audites.

- `app/Policies/ServicePolicy.php`
  Regles d'acces des services audites, y compris contribution terrain et gouvernance.

- `app/Providers/AppServiceProvider.php`
  Enregistrement des policies Phase 2 et route-bindings securises pour `service` et `mission_document`.

- `app/Services/Iam/SecurityAuditService.php`
  Journalisation des evenements Phase 2: service cree/mis a jour, entretien demarre/complete, document ajoute/supprime, consolidation generee.

- `database/migrations/2026_05_16_100000_phase2_enrich_services_table.php`
  Enrichissement de la table `services` avec les champs metier et soft deletes.

- `database/migrations/2026_05_16_100001_phase2_enrich_entretiens_table.php`
  Enrichissement de la table `entretiens` avec conducteur, statut, validation et synthese.

- `database/migrations/2026_05_16_100002_create_department_audit_consolidations_table.php`
  Creation de la table des consolidations departementales.

- `database/migrations/2026_05_16_100003_create_mission_documents_table.php`
  Creation du porte-documents relationnel par mission/service/entretien.

- `database/migrations/2026_05_16_100004_create_mission_swot_previews_table.php`
  Creation de la table placeholder SWOT.

- `database/migrations/2026_05_16_100005_create_mission_raci_previews_table.php`
  Creation de la table placeholder RACI.

- `resources/views/entretiens/conduite.blade.php`
  Ajout de l'affichage de progression et du statut pendant la conduite de l'entretien.

- `resources/views/entretiens/index.blade.php`
  Ajout de la colonne statut et de l'action de completion d'entretien.

- `resources/views/missions/show.blade.php`
  Ajout des cartes KPI mission, du formulaire de consolidation et du rappel SWOT/RACI.

- `resources/views/services/documents/index.blade.php`
  Nouvelle vue DGCPT du porte-documents par service.

- `resources/views/services/edit.blade.php`
  Nouvelle vue DGCPT d'edition des services audites.

- `resources/views/services/index.blade.php`
  Nouvelle vue DGCPT de pilotage des services audites avec progression, risques, documents et actions.

- `routes/web.php`
  Ajout des routes mission-scopees pour services, documents, consolidation et completion d'entretien.

- `tests/Feature/Missions/Phase2MissionServicesTest.php`
  Tests fonctionnels de creation de service, restrictions de policy et creation de consolidation.

### Resultat metier

- la mission n'est plus seulement un conteneur global;
- les services deviennent des unites auditees a part entiere;
- les documents et la consolidation sont desormais structures;
- la plateforme est prete pour SWOT, RACI, rapports par service et IA de synthese.

---

## Commit `ca21d0d`

Message: `fix(bootstrap): n'enregistrer Horizon que si laravel/horizon est présent dans vendor`

Objet: correction de l'environnement local pour eviter l'erreur `Class "Laravel\Horizon\HorizonApplicationServiceProvider" not found` lorsque le package n'est pas present dans `vendor`.

### Fichier modifie

- `bootstrap/providers.php`
  Enregistrement conditionnel du provider Horizon avec `class_exists(...)` afin de ne pas bloquer les commandes artisan sur un poste local ou un environnement incomplet, sans casser la compatibilite Ubuntu ni la production.

### Resultat technique

- artisan ne depend plus d'un `vendor/laravel/horizon` present au moment du bootstrap;
- le comportement reste compatible avec un environnement ou Horizon est bien installe.

---

## Synthese finale

### Commits crees et pousses pendant la session

- `ac9bc06`
- `9b27832`
- `ca21d0d`

### Axes couverts

- questionnaires dynamiques institutionnels;
- entretiens structures et progressifs;
- risques identifies et validation humaine;
- services audites comme unite metier;
- documents par service;
- consolidation departementale;
- preparation SWOT / RACI;
- correction de bootstrap Horizon pour l'environnement local.

### Etat de livraison

- branche cible: `main`
- destination: `origin/main`
- commits pushes avec succes

