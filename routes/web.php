<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\ProcessusController;
use App\Http\Controllers\ActifController;
use App\Http\Controllers\RisqueController;
use App\Http\Controllers\CartographieController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EntretienController;
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

    Route::post('/services', [ServiceController::class,'store'])->name('services.store');


    /*
    |--------------------------------------------------------------------------
    | ENTRETIENS
    |--------------------------------------------------------------------------
    */

    Route::get('/services/{id}/entretiens', [EntretienController::class,'index'])->name('entretiens.index');

    Route::post('/entretiens', [EntretienController::class,'store'])->name('entretiens.store');


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
