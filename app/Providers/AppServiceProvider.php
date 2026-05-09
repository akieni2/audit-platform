<?php

namespace App\Providers;

use App\Models\Risque;
use App\Policies\RisquePolicy;
use App\Repositories\Contracts\RiskRepositoryInterface;
use App\Repositories\EloquentRiskRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RiskRepositoryInterface::class, EloquentRiskRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(Risque::class, RisquePolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
