<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuse l’accès aux comptes désactivés (session invalide côté métier).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if (! $user->isApproved()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($user->isPendingApproval()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Votre compte est en attente de validation par l\'administration DGCPT.']);
            }

            if ($user->isEnrollmentRejected()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Votre demande d\'accès a été refusée.']);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Accès refusé.']);
        }

        if (isset($user->active) && ! $user->active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Ce compte est désactivé.']);
        }

        return $next($request);
    }
}
