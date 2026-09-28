<?php

namespace App\Http\Middleware;

use App\Services\MobileMenuService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class DetectMobileApplication
{
    public function __construct(private readonly MobileMenuService $mobileMenus) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isMobileApp = $this->mobileMenus->isMobileApplication($request);
        $allowedKeys = $isMobileApp && $request->user()
            ? $this->mobileMenus->allowedKeys($request->user())
            : array_keys($this->mobileMenus->catalog());

        $request->attributes->set('is_mobile_app', $isMobileApp);
        $request->attributes->set('mobile_menu_keys', $allowedKeys);

        View::share([
            'isMobileApp' => $isMobileApp,
            'mobileMenuKeys' => $allowedKeys,
            'mobileMenuCatalog' => $this->mobileMenus->catalog(),
        ]);

        return $next($request);
    }
}
