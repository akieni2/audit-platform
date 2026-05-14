<?php

namespace App\Providers;

use App\Domain\Missions\Events\MissionGovernanceTransitioned;
use App\Domain\Questionnaires\Events\EntretienResponsesRecorded;
use App\Domain\Risk\Events\RiskClosed;
use App\Domain\Risk\Events\RiskMitigated;
use App\Domain\Risk\Events\RiskPromoted;
use App\Listeners\RefreshMissionRiskProjection;
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
use App\Models\WorkflowTemplate;
use App\Observers\RisqueObserver;
use App\Policies\DepartmentAuditConsolidationPolicy;
use App\Policies\EntretienPolicy;
use App\Policies\IdentifiedRiskPolicy;
use App\Policies\MissionDocumentPolicy;
use App\Policies\QuestionnaireTemplatePolicy;
use App\Policies\RisquePolicy;
use App\Policies\ServicePolicy;
use App\Policies\WorkflowTemplatePolicy;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Repositories\EloquentRiskRepository;
use App\Services\Governance\ExecutiveDashboardService;
use App\Support\DgcptPasswordRules;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(
            RiskRepositoryInterface::class,
            EloquentRiskRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Password Rules
        |--------------------------------------------------------------------------
        */

        Password::defaults(fn () => DgcptPasswordRules::defaults());

        /*
        |--------------------------------------------------------------------------
        | Policies
        |--------------------------------------------------------------------------
        */

        Gate::policy(Risque::class, RisquePolicy::class);
        Gate::policy(Mission::class, \App\Policies\MissionPolicy::class);
        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(Department::class, \App\Policies\DepartmentPolicy::class);

        Gate::policy(
            QuestionnaireTemplate::class,
            QuestionnaireTemplatePolicy::class
        );

        Gate::policy(
            WorkflowTemplate::class,
            WorkflowTemplatePolicy::class
        );

        Gate::policy(Entretien::class, EntretienPolicy::class);
        Gate::policy(IdentifiedRisk::class, IdentifiedRiskPolicy::class);

        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(MissionService::class, ServicePolicy::class);

        Gate::policy(MissionDocument::class, MissionDocumentPolicy::class);

        Gate::policy(
            DepartmentAuditConsolidation::class,
            DepartmentAuditConsolidationPolicy::class
        );

        /*
        |--------------------------------------------------------------------------
        | Route Bindings
        |--------------------------------------------------------------------------
        */

        Route::bind('service', function (string $value) {
            $user = auth()->user();

            abort_unless($user, 403);

            return MissionService::query()
                ->whereKey($value)
                ->whereHas(
                    'mission',
                    fn ($q) => $q->visibleToUser($user)
                )
                ->firstOrFail();
        });

        Route::bind('mission_document', function (string $value) {
            $user = auth()->user();

            abort_unless($user, 403);

            if (! Schema::hasTable('mission_documents')) {
                abort(404);
            }

            return MissionDocument::query()
                ->whereKey($value)
                ->whereHas(
                    'mission',
                    fn ($q) => $q->visibleToUser($user)
                )
                ->firstOrFail();
        });

        Route::bind('entretien', function (string $value) {
            $user = auth()->user();

            abort_unless($user, 403);

            return Entretien::query()
                ->whereKey($value)
                ->visibleToUser($user)
                ->firstOrFail();
        });

        Route::bind('mission', function (string $value) {
            $user = auth()->user();

            abort_unless($user, 403);

            return Mission::query()
                ->whereKey($value)
                ->visibleToUser($user)
                ->firstOrFail();
        });

        /*
        |--------------------------------------------------------------------------
        | Gates
        |--------------------------------------------------------------------------
        */

        Gate::define(
            'manageUsers',
            fn (?User $user): bool =>
                $user?->canAccessAdministrationMenu() ?? false
        );

        Gate::define(
            'viewAdminDashboard',
            fn (?User $user): bool =>
                $user?->canAccessAdministrationMenu() ?? false
        );

        Gate::define(
            'viewSecurityAuditLog',
            fn (?User $user): bool =>
                $user?->canAccessSecurityLogs() ?? false
        );

        Gate::define(
            'viewExecutiveDashboard',
            fn (?User $user): bool =>
                $user?->canViewExecutiveDashboard() ?? false
        );

        Gate::define(
            'manageDepartments',
            fn (?User $user): bool =>
                $user?->canManageDepartments() ?? false
        );

        Gate::define(
            'manageEnrollmentRequests',
            fn (?User $user): bool =>
                $user?->isInstitutionalSuperAdmin() ?? false
        );

        /*
        |--------------------------------------------------------------------------
        | Observers
        |--------------------------------------------------------------------------
        */

        Risque::observe(RisqueObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Rate Limiter
        |--------------------------------------------------------------------------
        */

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard Cache Flush
        |--------------------------------------------------------------------------
        */

        Mission::saved(function (): void {
            ExecutiveDashboardService::flushNationalKpisCache();
        });

        Mission::deleted(function (): void {
            ExecutiveDashboardService::flushNationalKpisCache();
        });

        /*
        |--------------------------------------------------------------------------
        | Domain Events
        |--------------------------------------------------------------------------
        */

        Event::listen(
            EntretienResponsesRecorded::class,
            RefreshMissionRiskProjection::class
        );

        Event::listen(
            RiskPromoted::class,
            RefreshMissionRiskProjection::class
        );

        Event::listen(
            MissionGovernanceTransitioned::class,
            function (): void {
                ExecutiveDashboardService::flushNationalKpisCache();
            }
        );

        Event::listen(
    RiskPromoted::class,
    function (): void {
        ExecutiveDashboardService::flushNationalKpisCache();
    }
);

Event::listen(
    RiskMitigated::class,
    function (): void {
        ExecutiveDashboardService::flushNationalKpisCache();
    }
);

Event::listen(
    RiskClosed::class,
    function (): void {
        ExecutiveDashboardService::flushNationalKpisCache();
    }
);
        /*
        |--------------------------------------------------------------------------
        | Horizon
        |--------------------------------------------------------------------------
        */

        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request): bool {
                $user = $request->user();

                return $user !== null
                    && $user->canAccessAdministrationMenu();
            });
        }
    }
}