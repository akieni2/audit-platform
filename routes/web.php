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
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\ModuleHubController;

/*
|--------------------------------------------------------------------------
| PAGE ACCUEIL
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| ZONE AUTHENTIFIE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE UTILISATEUR
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | MISSIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');

    Route::get('/missions/create', [MissionController::class, 'create'])->name('missions.create');

    Route::post('/missions', [MissionController::class, 'store'])->name('missions.store');


    /*
    |--------------------------------------------------------------------------
    | RAPPORT PDF
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{id}/rapport',[ReportController::class,'generate'])
        ->name('missions.rapport');


    /*
    |--------------------------------------------------------------------------
    | SERVICES AUDITS
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{id}/services', [ServiceController::class,'index'])->name('services.index');

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

    Route::get('/missions/{id}/constats', [ConstatController::class,'index'])->name('constats.index');

    Route::post('/constats', [ConstatController::class,'store'])->name('constats.store');


    /*
    |--------------------------------------------------------------------------
    | PROCESSUS
    |--------------------------------------------------------------------------
    */

    Route::get('/missions/{id}/processus', [ProcessusController::class,'index'])->name('processus.index');

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

    Route::get('/missions/{id}/cartographie', [CartographieController::class,'index'])
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
    | ADMINISTRATION (roles  rserv aux admins)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserRoleController::class, 'update'])->name('users.role.update');
    });

});


/*
|--------------------------------------------------------------------------
| AUTH (LOGIN / REGISTER / LOGOUT)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';