<?php

namespace App\Providers;

use App\Models\Risque;
use App\Models\User;
use App\Observers\RisqueObserver;
use App\Policies\RisquePolicy;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Repositories\EloquentRiskRepository;
use App\Support\DgcptPasswordRules;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(User::class, \App\Policies\UserPolicy::class);

        Gate::define('manageUsers', function (?User $user): bool {
            if (! $user) {
                return false;
            }

            if ($user->role === 'admin') {
                return true;
            }

            $slug = $user->institutionalRole?->slug;

            return $slug === 'super_admin'
                || $slug === 'admin'
                || $user->hasPermission('manage_users');
        });

        Gate::define('viewAdminDashboard', fn (?User $user): bool => Gate::forUser($user)->allows('manageUsers'));

        Gate::define('viewSecurityAuditLog', fn (?User $user): bool => Gate::forUser($user)->allows('manageUsers'));

        Gate::define('viewExecutiveDashboard', function (?User $user): bool {
            if (! $user) {
                return false;
            }

            return $user->isAdmin()
                || $user->institutionalRole?->slug === 'inspecteur_services'
                || $user->hasPermission('supervise')
                || $user->hasPermission('supervise_global');
        });

        Risque::observe(RisqueObserver::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
