<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Risque;
use App\Models\User;
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
