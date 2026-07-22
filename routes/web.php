<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentAuditConsolidationController;
use App\Http\Controllers\EntretienConduiteController;
use App\Http\Controllers\EntretienController;
use App\Http\Controllers\FormBuilderController;
use App\Http\Controllers\IdentifiedRiskController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionAuditGroupController;
use App\Http\Controllers\MissionDocumentController;
use App\Http\Controllers\MissionQuestionnaireWizardController;
use App\Http\Controllers\MissionTeamMemberController;
use App\Http\Controllers\Questionnaires\QuestionnaireTemplateController;
use App\Http\Controllers\ProcessusController;
use App\Http\Controllers\ActifController;
use App\Http\Controllers\RisqueController;
use App\Http\Controllers\CartographieController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ConstatController;
use App\Http\Controllers\ActionCorrectiveController;
use App\Http\Controllers\ControleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Auth\ForcedPasswordChangeController;
use App\Http\Controllers\EnterpriseCatalogController;
use App\Http\Controllers\ExecutiveAnalyticsController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\Iam\Admin\AdminDashboardController;
use App\Http\Controllers\Iam\Admin\DepartmentManagementController;
use App\Http\Controllers\Iam\Admin\EnrollmentApprovalController;
use App\Http\Controllers\Iam\Admin\SecurityAuditLogController;
use App\Http\Controllers\Iam\Admin\UserManagementController;
use App\Http\Controllers\ModuleHubController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\NotificationUnreadController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\QuestionnaireBuilderController;
use App\Http\Controllers\RiskReviewBoardController;
use App\Http\Controllers\RaciBuilderController;
use App\Http\Controllers\RaciRuntimeController;
use App\Http\Controllers\SwotBuilderController;
use App\Http\Controllers\SwotRuntimeController;
use App\Http\Controllers\WorkflowRuntimeController;
use App\Http\Controllers\WorkflowStageRuntimeController;
use App\Http\Controllers\WorkflowBuilderController;
use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\EnterpriseObservabilityController;

/*
|--------------------------------------------------------------------------
| PAGE ACCUEIL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->middleware('throttle:120,1')
    ->name('health.ready');


/*
|--------------------------------------------------------------------------
| ZONE AUTHENTIFI�E (auth + compte actif)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/password/changement-obligatoire', [ForcedPasswordChangeController::class, 'edit'])
        ->name('password.force.edit');
    Route::post('/password/changement-obligatoire', [ForcedPasswordChangeController::class, 'update'])
        ->name('password.force.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/dashboard/executive', ExecutiveDashboardController::class)
        ->middleware(['can:viewExecutiveDashboard'])
        ->name('dashboard.executive');

    /*
    |--------------------------------------------------------------------------
    | PROFILE UTILISATEUR
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');

    Route::get('/account/password', [AccountPasswordController::class, 'edit'])->name('account.password');

    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationCenterController::class, 'markRead'])->name('notifications.read');

    Route::get('/notifications/unread-count', NotificationUnreadController::class)
        ->middleware('throttle:120,1')
        ->name('notifications.unread-count');

    Route::get('/search', GlobalSearchController::class)->name('search');


    /*
    |--------------------------------------------------------------------------
    | MISSIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');

    Route::get('/missions/create', [MissionController::class, 'create'])->name('missions.create');

    Route::post('/missions', [MissionController::class, 'store'])->name('missions.store');

    Route::get('/missions/{mission}', [MissionController::class, 'show'])->name('missions.show');
    Route::get('/missions/{mission}/edit', [MissionController::class, 'edit'])->name('missions.edit');
    Route::put('/missions/{mission}', [MissionController::class, 'update'])->name('missions.update');
    Route::delete('/missions/{mission}', [MissionController::class, 'destroy'])->name('missions.destroy');
    Route::post('/missions/{mission}/workflow', [MissionController::class, 'workflow'])->name('missions.workflow');

    Route::post('/missions/{mission}/team-members', [MissionTeamMemberController::class, 'store'])
        ->name('missions.team-members.store');
    Route::delete('/missions/{mission}/team-members/{team_member}', [MissionTeamMemberController::class, 'destroy'])
        ->name('missions.team-members.destroy');
    Route::post('/missions/{mission}/audit-groups', [MissionAuditGroupController::class, 'store'])->name('missions.audit-groups.store');
    Route::patch('/missions/{mission}/audit-groups/{audit_group}', [MissionAuditGroupController::class, 'update'])->name('missions.audit-groups.update');
    Route::delete('/missions/{mission}/audit-groups/{audit_group}', [MissionAuditGroupController::class, 'destroy'])->name('missions.audit-groups.destroy');
    Route::post('/missions/{mission}/audit-groups/{audit_group}/questionnaire-import', [MissionAuditGroupController::class, 'importQuestionnaire'])->name('missions.audit-groups.import');
    Route::get('/missions/{mission}/questionnaires/assistant', [MissionQuestionnaireWizardController::class, 'create'])->name('missions.questionnaires.wizard.create');
    Route::post('/missions/{mission}/questionnaires/assistant', [MissionQuestionnaireWizardController::class, 'store'])->name('missions.questionnaires.wizard.store');
    Route::post('/missions/{mission}/questionnaires/{template}/submit-review', [MissionQuestionnaireWizardController::class, 'submitReview'])->name('missions.questionnaires.submit-review');
    Route::post('/missions/{mission}/questionnaires/{template}/review', [MissionQuestionnaireWizardController::class, 'review'])->name('missions.questionnaires.review');
    Route::post('/missions/{mission}/questionnaires/{template}/adopt', [MissionQuestionnaireWizardController::class, 'adopt'])->name('missions.questionnaires.adopt');


    /*
    |--------------------------------------------------------------------------
    | RAPPORT PDF
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{mission}/rapport', [ReportController::class, 'generate'])
        ->name('missions.rapport');


    /*
    |--------------------------------------------------------------------------
    | SERVICES AUDIT�S
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{mission}/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/missions/{mission}/services', [ServiceController::class, 'store'])->name('missions.services.store');
    Route::get('/missions/{mission}/services/{service}/edit', [ServiceController::class, 'edit'])->name('missions.services.edit');
    Route::put('/missions/{mission}/services/{service}', [ServiceController::class, 'update'])->name('missions.services.update');
    Route::delete('/missions/{mission}/services/{service}', [ServiceController::class, 'destroy'])->name('missions.services.destroy');

    Route::get('/missions/{mission}/services/{service}/documents', [MissionDocumentController::class, 'index'])->name('missions.services.documents.index');
    Route::post('/missions/{mission}/services/{service}/documents', [MissionDocumentController::class, 'store'])->name('missions.services.documents.store');
    Route::delete('/mission-documents/{mission_document}', [MissionDocumentController::class, 'destroy'])->name('mission-documents.destroy');
    Route::get('/mission-documents/{mission_document}/download', [MissionDocumentController::class, 'download'])->name('mission-documents.download');

    Route::post('/missions/{mission}/consolidations', [DepartmentAuditConsolidationController::class, 'store'])->name('missions.consolidations.store');


    /*
    |--------------------------------------------------------------------------
    | ENTRETIENS
    |--------------------------------------------------------------------------
    */

    Route::get('/services/{id}/entretiens', [EntretienController::class,'index'])->name('entretiens.index');

    Route::get('/entretiens/{entretien}/conduite', [EntretienConduiteController::class, 'show'])
        ->name('entretiens.conduite.show');
    Route::post('/entretiens/{entretien}/reponses-dynamiques', [EntretienConduiteController::class, 'storeResponses'])
        ->name('entretiens.dynamic-responses.store');
    Route::post('/entretiens/{entretien}/completer', [EntretienController::class, 'complete'])
        ->name('entretiens.complete');
    Route::patch('/entretiens/{entretien}/questionnaire', [EntretienController::class, 'attachTemplate'])
        ->name('entretiens.questionnaire.attach');

    Route::patch('/identified-risks/{identified_risk}/valider', [IdentifiedRiskController::class, 'validateHuman'])
        ->name('identified-risks.validate');
    Route::patch('/identified-risks/{identified_risk}/submit-review', [IdentifiedRiskController::class, 'submitForReview'])
        ->name('identified-risks.submit-review');
    Route::patch('/identified-risks/{identified_risk}/approve', [IdentifiedRiskController::class, 'approve'])
        ->name('identified-risks.approve');
    Route::patch('/identified-risks/{identified_risk}/reject', [IdentifiedRiskController::class, 'reject'])
        ->name('identified-risks.reject');
    Route::patch('/identified-risks/{identified_risk}/promote', [IdentifiedRiskController::class, 'promote'])
        ->name('identified-risks.promote');

    Route::post('/entretiens', [EntretienController::class, 'store'])->name('entretiens.store');

    Route::resource('questionnaire-templates', QuestionnaireTemplateController::class)
        ->except(['show']);
    Route::post('/questionnaire-templates/{questionnaire_template}/duplicate', [QuestionnaireTemplateController::class, 'duplicate'])
        ->name('questionnaire-templates.duplicate');
    Route::post('/questionnaire-templates/{questionnaire_template}/sections', [QuestionnaireTemplateController::class, 'storeSection'])
        ->name('questionnaire-templates.sections.store');
    Route::delete('/questionnaire-templates/{questionnaire_template}/sections/{section}', [QuestionnaireTemplateController::class, 'destroySection'])
        ->name('questionnaire-templates.sections.destroy');
    Route::post('/questionnaire-templates/{questionnaire_template}/sections/{section}/questions', [QuestionnaireTemplateController::class, 'storeQuestion'])
        ->name('questionnaire-templates.questions.store');
    Route::delete('/questionnaire-templates/{questionnaire_template}/sections/{section}/questions/{question}', [QuestionnaireTemplateController::class, 'destroyQuestion'])
        ->name('questionnaire-templates.questions.destroy');

    Route::prefix('questionnaire-builder')
        ->name('questionnaire-builder.')
        ->group(function () {
            Route::get('/', [QuestionnaireBuilderController::class, 'index'])->name('index');
            Route::get('/{template}/edit', [QuestionnaireBuilderController::class, 'edit'])->name('edit');
            Route::post('/templates', [QuestionnaireBuilderController::class, 'storeTemplate'])->name('templates.store');
            Route::patch('/templates/{template}', [QuestionnaireBuilderController::class, 'updateTemplate'])->name('templates.update');
            Route::post('/{template}/sections', [QuestionnaireBuilderController::class, 'storeSection'])->name('sections.store');
            Route::patch('/sections/{section}', [QuestionnaireBuilderController::class, 'updateSection'])->name('sections.update');
            Route::delete('/sections/{section}', [QuestionnaireBuilderController::class, 'destroySection'])->name('sections.destroy');
            Route::post('/sections/{section}/questions', [QuestionnaireBuilderController::class, 'storeQuestion'])->name('questions.store');
            Route::patch('/questions/{question}', [QuestionnaireBuilderController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('/questions/{question}', [QuestionnaireBuilderController::class, 'destroyQuestion'])->name('questions.destroy');
            Route::post('/questions/reorder', [QuestionnaireBuilderController::class, 'reorderQuestions'])->name('questions.reorder');
            Route::post('/sections/reorder', [QuestionnaireBuilderController::class, 'reorderSections'])->name('sections.reorder');
            Route::post('/templates/{template}/publish', [QuestionnaireBuilderController::class, 'publish'])->name('templates.publish');
            Route::post('/templates/{template}/archive', [QuestionnaireBuilderController::class, 'archive'])->name('templates.archive');
        });

    Route::prefix('workflow-builder')
        ->name('workflow-builder.')
        ->group(function () {
            Route::get('/', [WorkflowBuilderController::class, 'index'])->name('index');
            Route::get('/create', [WorkflowBuilderController::class, 'create'])->name('create');
            Route::get('/{template}/edit', [WorkflowBuilderController::class, 'edit'])->name('edit');
            Route::post('/templates', [WorkflowBuilderController::class, 'storeTemplate'])->name('store');
            Route::patch('/{template}', [WorkflowBuilderController::class, 'updateTemplate'])->name('update');
            Route::post('/{template}/stages', [WorkflowBuilderController::class, 'storeStage'])->name('stages.store');
            Route::patch('/stages/{stage}', [WorkflowBuilderController::class, 'updateStage'])->name('stages.update');
            Route::patch('/stages/{stage}/layout', [WorkflowBuilderController::class, 'updateStageLayout'])->name('stages.layout');
            Route::delete('/stages/{stage}', [WorkflowBuilderController::class, 'destroyStage'])->name('stages.destroy');
            Route::post('/{template}/transitions', [WorkflowBuilderController::class, 'storeTransition'])->name('transitions.store');
            Route::delete('/transitions/{transition}', [WorkflowBuilderController::class, 'destroyTransition'])->name('transitions.destroy');
            Route::post('/templates/{template}/publish', [WorkflowBuilderController::class, 'publish'])->name('publish');
            Route::post('/templates/{template}/archive', [WorkflowBuilderController::class, 'archive'])->name('archive');
        });

    Route::prefix('form-builder')
        ->name('form-builder.')
        ->group(function () {
            Route::get('/', [FormBuilderController::class, 'index'])->name('index');
            Route::get('/create', [FormBuilderController::class, 'create'])->name('create');
            Route::get('/{template}/edit', [FormBuilderController::class, 'edit'])->name('edit');
            Route::post('/templates', [FormBuilderController::class, 'storeTemplate'])->name('store');
            Route::patch('/{template}', [FormBuilderController::class, 'updateTemplate'])->name('update');
            Route::post('/{template}/fields', [FormBuilderController::class, 'storeField'])->name('fields.store');
            Route::patch('/fields/{field}', [FormBuilderController::class, 'updateField'])->name('fields.update');
            Route::delete('/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('fields.destroy');
            Route::post('/fields/reorder', [FormBuilderController::class, 'reorderFields'])->name('fields.reorder');
            Route::post('/templates/{template}/publish', [FormBuilderController::class, 'publish'])->name('publish');
            Route::post('/templates/{template}/archive', [FormBuilderController::class, 'archive'])->name('archive');
        });

    Route::prefix('swot-builder')
        ->name('swot-builder.')
        ->group(function () {
            Route::get('/', [SwotBuilderController::class, 'index'])->name('index');
            Route::get('/{template}/edit', [SwotBuilderController::class, 'edit'])->name('edit');
            Route::post('/templates', [SwotBuilderController::class, 'storeTemplate'])->name('store');
            Route::patch('/{template}', [SwotBuilderController::class, 'updateTemplate'])->name('update');
            Route::post('/{template}/categories', [SwotBuilderController::class, 'storeCategory'])->name('categories.store');
            Route::post('/{template}/entries', [SwotBuilderController::class, 'storeEntry'])->name('entries.store');
        });

    Route::prefix('raci-builder')
        ->name('raci-builder.')
        ->group(function () {
            Route::get('/', [RaciBuilderController::class, 'index'])->name('index');
            Route::get('/{template}/edit', [RaciBuilderController::class, 'edit'])->name('edit');
            Route::post('/templates', [RaciBuilderController::class, 'storeTemplate'])->name('store');
            Route::patch('/{template}', [RaciBuilderController::class, 'updateTemplate'])->name('update');
            Route::post('/{template}/roles', [RaciBuilderController::class, 'storeRole'])->name('roles.store');
            Route::post('/{template}/assignments', [RaciBuilderController::class, 'storeAssignment'])->name('assignments.store');
        });

    Route::get('/workflows/dashboard', [WorkflowRuntimeController::class, 'dashboard'])
        ->name('workflow-runtime.dashboard');
    Route::get('/workflows/observability', [WorkflowRuntimeController::class, 'observability'])
        ->name('workflow-runtime.observability');

    Route::prefix('observability/enterprise')
        ->name('observability.enterprise.')
        ->group(function (): void {
            Route::get('/health', [EnterpriseObservabilityController::class, 'enterpriseHealth'])->name('health');
            Route::get('/diagnostics', [EnterpriseObservabilityController::class, 'diagnostics'])->name('diagnostics');
            Route::get('/security', [EnterpriseObservabilityController::class, 'security'])->name('security');
            Route::get('/queues', [EnterpriseObservabilityController::class, 'queues'])->name('queues');
            Route::get('/performance', [EnterpriseObservabilityController::class, 'performance'])->name('performance');
            Route::get('/ai', [EnterpriseObservabilityController::class, 'aiMonitoring'])->name('ai');
        });

    Route::prefix('ai')->name('ai.')->group(function (): void {
        Route::get('/', [AiCopilotController::class, 'index'])->name('index');
        Route::get('/analytics', [AiCopilotController::class, 'analytics'])->name('analytics');
        Route::get('/recommendations', [AiCopilotController::class, 'recommendations'])->name('recommendations');
        Route::post('/recommendations/{recommendation}/accept', [AiCopilotController::class, 'acceptRecommendation'])->name('recommendations.accept');

        Route::middleware(['tenant.enforce'])->group(function (): void {
            Route::get('/missions/{mission}', [AiCopilotController::class, 'copilotForMission'])->name('mission');
            Route::get('/missions/{mission}/assistant', [AiCopilotController::class, 'assistant'])->name('assistant');
            Route::get('/missions/{mission}/recommendations', [AiCopilotController::class, 'recommendations'])->name('recommendations.mission');
            Route::post('/missions/{mission}/ask', [AiCopilotController::class, 'ask'])->name('ask');
            Route::post('/missions/{mission}/audit-summary', [AiCopilotController::class, 'auditSummary'])->name('audit.summary');
            Route::post('/missions/{mission}/audit-questions', [AiCopilotController::class, 'auditQuestions'])->name('audit.questions');
            Route::post('/missions/{mission}/risk-analyze', [AiCopilotController::class, 'riskAnalysis'])->name('risk.analyze');
            Route::post('/missions/{mission}/control-analyze', [AiCopilotController::class, 'controlAnalysis'])->name('control.analyze');
        });
    });

    Route::middleware(['tenant.enforce'])->group(function (): void {
    Route::get('/missions/{mission}/workflow/runtime', [WorkflowRuntimeController::class, 'show'])
        ->name('workflow-runtime.show');
    Route::post('/missions/{mission}/workflow/runtime/actions', [WorkflowRuntimeController::class, 'transition'])
        ->name('workflow-runtime.transition');
    Route::get('/missions/{mission}/workflow/runtime/current', [WorkflowStageRuntimeController::class, 'showCurrent'])
        ->name('workflow-runtime.current');
    Route::get('/missions/{mission}/workflow/runtime/stages/{stage}', [WorkflowStageRuntimeController::class, 'showStage'])
        ->name('workflow-runtime.stage');
    Route::post('/missions/{mission}/workflow/runtime/stages/{stage}', [WorkflowStageRuntimeController::class, 'submitStage'])
        ->name('workflow-runtime.stage.submit');

    Route::get('/missions/{mission}/swot', [SwotRuntimeController::class, 'show'])->name('swot.show');
    Route::post('/missions/{mission}/swot/analyze', [SwotRuntimeController::class, 'analyze'])->name('swot.analyze');
    Route::get('/missions/{mission}/swot/recommendations', [SwotRuntimeController::class, 'recommendations'])->name('swot.recommendations');

    Route::get('/missions/{mission}/raci', [RaciRuntimeController::class, 'show'])->name('raci.show');
    Route::post('/missions/{mission}/raci/assignments', [RaciRuntimeController::class, 'assignments'])->name('raci.assignments');
    Route::post('/missions/{mission}/raci/validation', [RaciRuntimeController::class, 'validation'])->name('raci.validation');
    Route::get('/missions/{mission}/raci/analytics', [RaciRuntimeController::class, 'analytics'])->name('raci.analytics');
    });

    Route::get('/swot/consolidation', [SwotRuntimeController::class, 'consolidation'])->name('swot.consolidation');

    Route::prefix('enterprise')
        ->name('enterprise.')
        ->group(function () {
            Route::get('/methodologies', [EnterpriseCatalogController::class, 'methodologies'])->name('methodologies');
            Route::get('/taxonomies', [EnterpriseCatalogController::class, 'taxonomies'])->name('taxonomies');
            Route::get('/controls', [EnterpriseCatalogController::class, 'controls'])->name('controls');
            Route::get('/consolidation', [EnterpriseCatalogController::class, 'consolidation'])->name('consolidation');
            Route::get('/swot', [SwotRuntimeController::class, 'consolidation'])->name('swot');
        });

    Route::prefix('executive')
        ->middleware(['can:viewExecutiveDashboard'])
        ->name('executive.')
        ->group(function () {
            Route::get('/national-dashboard', [ExecutiveAnalyticsController::class, 'nationalDashboard'])->name('national-dashboard');
            Route::get('/department-comparison', [ExecutiveAnalyticsController::class, 'departmentComparison'])->name('department-comparison');
            Route::get('/risk-intelligence', [ExecutiveAnalyticsController::class, 'riskIntelligence'])->name('risk-intelligence');
            Route::get('/maturity-index', [ExecutiveAnalyticsController::class, 'maturityIndex'])->name('maturity-index');
            Route::get('/governance-overview', [ExecutiveAnalyticsController::class, 'governanceOverview'])->name('governance-overview');
            Route::get('/swot-dashboard', [ExecutiveAnalyticsController::class, 'swotDashboard'])->name('swot-dashboard');
            Route::get('/raci-dashboard', [ExecutiveAnalyticsController::class, 'raciDashboard'])->name('raci-dashboard');
            Route::get('/organizational-analysis', [ExecutiveAnalyticsController::class, 'organizationalAnalysis'])->name('organizational-analysis');
        });


    /*
    |--------------------------------------------------------------------------
    | CONSTATS D'AUDIT
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{mission}/constats', [ConstatController::class, 'index'])->name('constats.index');

    Route::post('/constats', [ConstatController::class,'store'])->name('constats.store');


    /*
    |--------------------------------------------------------------------------
    | PROCESSUS
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{mission}/processus', [ProcessusController::class, 'index'])->name('processus.index');

    Route::post('/processus', [ProcessusController::class,'store'])->name('processus.store');


    /*
    |--------------------------------------------------------------------------
    | ACTIFS
    |--------------------------------------------------------------------------
    */

    Route::get('/processus/{id}/actifs', [ActifController::class,'index'])->name('actifs.index');

    Route::post('/actifs', [ActifController::class,'store'])->name('actifs.store');


    /*
    |--------------------------------------------------------------------------
    | RISQUES
    |--------------------------------------------------------------------------
    */

    Route::get('/actifs/{id}/risques', [RisqueController::class,'index'])->name('risques.index');

    Route::post('/risques', [RisqueController::class,'store'])->name('risques.store');

    Route::patch('/risques/{risque}', [RisqueController::class, 'update'])
        ->name('risques.update');
    Route::patch('/risques/{risque}/owner', [RisqueController::class, 'assignOwner'])
        ->name('risques.assign-owner');
    Route::patch('/risques/{risque}/mitigate', [RisqueController::class, 'mitigate'])
        ->name('risques.mitigate');
    Route::patch('/risques/{risque}/close', [RisqueController::class, 'close'])
        ->name('risques.close');
    Route::patch('/risques/{risque}/archive', [RisqueController::class, 'archive'])
        ->name('risques.archive');
    Route::get('/risks/review-board', [RiskReviewBoardController::class, 'index'])
        ->name('risks.review-board');


    /*
    |--------------------------------------------------------------------------
    | ACTIONS CORRECTIVES
    |--------------------------------------------------------------------------
    */

    Route::get('/risques/{id}/actions',[ActionCorrectiveController::class,'index'])->name('actions.index');

    Route::post('/risques/{id}/actions',[ActionCorrectiveController::class,'store'])->name('actions.store');


    /*
    |--------------------------------------------------------------------------
    | CONTROLES
    |--------------------------------------------------------------------------
    */

    Route::get('/risques/{id}/controles',[ControleController::class,'index'])
        ->name('controles.index');

    Route::post('/controles',[ControleController::class,'store'])
        ->name('controles.store');


    /*
    |--------------------------------------------------------------------------
    | CARTOGRAPHIE DES RISQUES
    |--------------------------------------------------------------------------
    */

    Route::get('/cartographie', [CartographieController::class,'select'])
        ->name('cartographie.select');

    Route::get('/missions/{mission}/cartographie', [CartographieController::class, 'index'])
        ->name('cartographie.index');

    /*
    |--------------------------------------------------------------------------
    | DGCPT — HIÉRARCHIE & CONSOLIDATION MÉTIER
    |--------------------------------------------------------------------------
    */

    Route::prefix('dgcpt')->name('dgcpt.')->group(function (): void {
        Route::get('/hierarchie', [\App\Http\Controllers\Dgcpt\DgcptHierarchyController::class, 'index'])
            ->name('hierarchy.index');
        Route::get('/consolidation/nationale', [\App\Http\Controllers\Dgcpt\DgcptHierarchyController::class, 'national'])
            ->name('consolidation.national');
        Route::get('/consolidation/province/{treasuryEntity}', [\App\Http\Controllers\Dgcpt\DgcptHierarchyController::class, 'province'])
            ->name('consolidation.province');
        Route::get('/import-questionnaire', [\App\Http\Controllers\Dgcpt\QuestionnaireImportController::class, 'index'])
            ->name('questionnaire-import.index');
        Route::post('/import-questionnaire', [\App\Http\Controllers\Dgcpt\QuestionnaireImportController::class, 'store'])
            ->name('questionnaire-import.store');
        Route::get('/questionnaires/{template}/source', [\App\Http\Controllers\Dgcpt\QuestionnaireImportController::class, 'downloadSource'])
            ->name('questionnaire-import.source');
    });

    /*
    |--------------------------------------------------------------------------
    | HUBS MODULES (menus Analyse / Suivi)
    |--------------------------------------------------------------------------
    */

    Route::get('/module/entretiens', [ModuleHubController::class, 'entretiens'])->name('module.entretiens');
    Route::get('/module/processus', [ModuleHubController::class, 'processus'])->name('module.processus');
    Route::get('/module/actifs', [ModuleHubController::class, 'actifs'])->name('module.actifs');
    Route::get('/module/risques', [ModuleHubController::class, 'risques'])->name('module.risques');
    Route::get('/module/actions-correctives', [ModuleHubController::class, 'actionsCorrectives'])->name('module.actions');
    Route::get('/module/rapports', [ModuleHubController::class, 'rapports'])->name('module.rapports');
    Route::get('/module/questionnaires', [ModuleHubController::class, 'questionnaires'])->name('module.questionnaires');


    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION CENTRALE
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::middleware(['can:manageEnrollmentRequests'])->group(function (): void {
            Route::get('/enrollments', [EnrollmentApprovalController::class, 'index'])->name('enrollments.index');
            Route::get('/enrollments/pending-count', [EnrollmentApprovalController::class, 'pendingCount'])
                ->middleware('throttle:120,1')
                ->name('enrollments.pending-count');
            Route::get('/enrollments/{user}/review', [EnrollmentApprovalController::class, 'review'])->name('enrollments.review');
            Route::post('/enrollments/{user}/approve', [EnrollmentApprovalController::class, 'approve'])->name('enrollments.approve');
            Route::post('/enrollments/{user}/reject', [EnrollmentApprovalController::class, 'reject'])->name('enrollments.reject');
        });

        Route::middleware(['can:manageUsers'])->group(function (): void {
            Route::get('/', AdminDashboardController::class)->name('home');
            Route::get('/security/audit-logs', [SecurityAuditLogController::class, 'index'])->name('security.audit-logs');

            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
            Route::post('/users/{user}/password-reset', [UserManagementController::class, 'sendPasswordReset'])->name('users.password-reset');
            Route::post('/users/{user}/temporary-password', [UserManagementController::class, 'generateTemporaryPassword'])->name('users.temporary-password');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        });

        Route::get('/departments/organigramme', [DepartmentManagementController::class, 'organigramme'])
            ->name('departments.organigramme');
        Route::post('/departments/organigramme/structures', [DepartmentManagementController::class, 'visualStore'])
            ->name('departments.visual-store');
        Route::patch('/departments/{department}/organigramme/move', [DepartmentManagementController::class, 'visualMove'])
            ->name('departments.visual-move');
        Route::patch('/departments/{department}/organigramme/position', [DepartmentManagementController::class, 'visualPosition'])
            ->name('departments.visual-position');
        Route::patch('/departments/{department}/organigramme/supervisor', [DepartmentManagementController::class, 'visualSupervisor'])
            ->name('departments.visual-supervisor');

        Route::middleware(['can:manageDepartments'])->group(function (): void {
            Route::resource('departments', DepartmentManagementController::class)->except(['show']);
        });
    });

});


/*
|--------------------------------------------------------------------------
| AUTH (LOGIN / REGISTER / LOGOUT)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
