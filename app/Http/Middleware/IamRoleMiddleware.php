<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie le slug du rôle institutionnel (table roles) ou le rôle legacy admin.
 * Usage : Route::middleware('iam.role:inspecteur_services')
 */
class IamRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $instSlug = $user->institutionalRole?->slug;
        if ($instSlug !== null && in_array($instSlug, $slugs, true)) {
            return $next($request);
        }

        abort(403, 'Accès réservé aux profils autorisés.');
    }
}
