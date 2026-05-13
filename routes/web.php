<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentAuditConsolidationController;
use App\Http\Controllers\EntretienConduiteController;
use App\Http\Controllers\EntretienController;
use App\Http\Controllers\IdentifiedRiskController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionDocumentController;
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
| ZONE AUTHENTIFIE (auth + compte actif)
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
    Route::post('/missions/{mission}/workflow', [MissionController::class, 'workflow'])->name('missions.workflow');

    Route::post('/missions/{mission}/team-members', [MissionTeamMemberController::class, 'store'])
        ->name('missions.team-members.store');
    Route::delete('/missions/{mission}/team-members/{team_member}', [MissionTeamMemberController::class, 'destroy'])
        ->name('missions.team-members.destroy');


    /*
    |--------------------------------------------------------------------------
    | RAPPORT PDF
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{mission}/rapport', [ReportController::class, 'generate'])
        ->name('missions.rapport');


    /*
    |--------------------------------------------------------------------------
    | SERVICES AUDITS
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
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        });

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
