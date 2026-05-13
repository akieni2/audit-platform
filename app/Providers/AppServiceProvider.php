<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\DepartmentAuditConsolidation;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionService;
use App\Models\QuestionnaireTemplate;
use App\Models\Risque;
use App\Models\Service;
use App\Models\User;
use App\Policies\DepartmentAuditConsolidationPolicy;
use App\Policies\EntretienPolicy;
use App\Policies\IdentifiedRiskPolicy;
use App\Policies\MissionDocumentPolicy;
use App\Policies\QuestionnaireTemplatePolicy;
use App\Policies\ServicePolicy;
use App\Services\Governance\ExecutiveDashboardService;
use App\Observers\RisqueObserver;
use App\Policies\RisquePolicy;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Repositories\EloquentRiskRepository;
use App\Support\DgcptPasswordRules;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RiskRepositoryInterface::class, EloquentRiskRepository::class);
    }

    public function boot(): void
    {
        Password::defaults(fn () => DgcptPasswordRules::defaults());

        Gate::policy(Risque::class, RisquePolicy::class);
        Gate::policy(Mission::class, \App\Policies\MissionPolicy::class);
        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(Department::class, \App\Policies\DepartmentPolicy::class);
        Gate::policy(QuestionnaireTemplate::class, QuestionnaireTemplatePolicy::class);
        Gate::policy(Entretien::class, EntretienPolicy::class);
        Gate::policy(IdentifiedRisk::class, IdentifiedRiskPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(MissionService::class, ServicePolicy::class);
        Gate::policy(MissionDocument::class, MissionDocumentPolicy::class);
        Gate::policy(DepartmentAuditConsolidation::class, DepartmentAuditConsolidationPolicy::class);

        Route::bind('service', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);

            return MissionService::query()
                ->whereKey($value)
                ->whereHas('mission', fn ($q) => $q->visibleToUser($user))
                ->firstOrFail();
        });

        Route::bind('mission_document', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);
            abort_unless(Schema::hasTable('mission_documents'), 404);

            return MissionDocument::query()
                ->whereKey($value)
                ->whereHas('mission', fn ($q) => $q->visibleToUser($user))
                ->firstOrFail();
        });

        Route::bind('entretien', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);

            return Entretien::query()->whereKey($value)->visibleToUser($user)->firstOrFail();
        });

        Route::bind('mission', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);

            return Mission::query()->whereKey($value)->visibleToUser($user)->firstOrFail();
        });

        Gate::define('manageUsers', fn (?User $user): bool => $user?->canAccessAdministrationMenu() ?? false);

        Gate::define('viewAdminDashboard', fn (?User $user): bool => $user?->canAccessAdministrationMenu() ?? false);

        Gate::define('viewSecurityAuditLog', fn (?User $user): bool => $user?->canAccessSecurityLogs() ?? false);

        Gate::define('viewExecutiveDashboard', fn (?User $user): bool => $user?->canViewExecutiveDashboard() ?? false);

        Gate::define('manageDepartments', fn (?User $user): bool => $user?->canManageDepartments() ?? false);

        Gate::define('manageEnrollmentRequests', fn (?User $user): bool => $user?->isInstitutionalSuperAdmin() ?? false);

        Risque::observe(RisqueObserver::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        Mission::saved(function (): void {
            ExecutiveDashboardService::flushNationalKpisCache();
        });

        Mission::deleted(function (): void {
            ExecutiveDashboardService::flushNationalKpisCache();
        });

        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                $user = $request->user();

                return $user !== null && $user->canAccessAdministrationMenu();
            });
        }
    }
}
