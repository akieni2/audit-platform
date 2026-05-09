<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Supervision nationale ou gestion technique globale (Inspecteur des Services, super_admin, admin legacy).
 */
class SupervisionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $slug = $user->institutionalRole?->slug;
        if (in_array($slug, ['inspecteur_services', 'super_admin'], true)) {
            return $next($request);
        }

        if ($user->hasPermission('supervise_global')) {
            return $next($request);
        }

        abort(403, 'Supervision nationale requise.');
    }
}
