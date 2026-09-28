<?php

namespace App\Http\Middleware;

use App\Services\MobileMenuService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceMobileMenuAccess
{
    public function __construct(private readonly MobileMenuService $mobileMenus) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user !== null
            && $this->mobileMenus->isMobileApplication($request)
            && ! $this->mobileMenus->canAccessRoute($user, $request->route()?->getName())
        ) {
            abort(403, 'Ce module n’est pas habilité dans votre application mobile. Contactez l’administration.');
        }

        return $next($request);
    }
}
