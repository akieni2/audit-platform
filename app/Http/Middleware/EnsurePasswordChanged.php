<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers le changement obligatoire tant que must_change_password est actif (première connexion).
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs([
            'password.force.edit',
            'password.force.update',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
        ])) {
            return $next($request);
        }

        if ($request->routeIs('verification.*')) {
            return $next($request);
        }

        return redirect()->route('password.force.edit');
    }
}
