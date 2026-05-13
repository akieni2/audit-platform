<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{

    public function handle(Request $request, Closure $next, $role)
    {

        if (!Auth::check()) {
            return redirect('/login');
        }

        if (!Auth::user()->hasLegacyRole((string) $role)) {
            abort(403, 'Accès non autorisé');
        }

        return $next($request);
    }
}
