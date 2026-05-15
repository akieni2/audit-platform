<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LoadIamContext::class,
            \App\Http\Middleware\ResolveTenantContext::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'iam.role' => \App\Http\Middleware\IamRoleMiddleware::class,
            'supervision' => \App\Http\Middleware\SupervisionMiddleware::class,
            'must_change_password' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'tenant.resolve' => \App\Http\Middleware\ResolveTenantContext::class,
            'tenant.enforce' => \App\Http\Middleware\EnforceTenantIsolation::class,
            'api.hardening' => \App\Http\Middleware\EnforceApiHardening::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('queue:prune-failed --hours=336')->weekly();
    })
    ->create();
