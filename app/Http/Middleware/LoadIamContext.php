<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Précharge le contexte IAM (rôle institutionnel, permissions, département) pour chaque requête authentifiée.
 */
class LoadIamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null) {
            $user->loadIamRelations();

            View::share([
                'iamContextReady' => true,
                'canManageUsers' => $user->canAccessAdministrationMenu() || $user->canManageDepartmentUsers(),
                'canManageDepartmentsNav' => $user->canManageDepartments(),
                'canViewOrganizationChartNav' => $user->canAccessOrganizationChart(),
                'canViewExecutiveNav' => $user->canViewExecutiveDashboard(),
                'canAccessCopriNav' => $user->canAccessCopriMenu(),
            ]);
        }

        return $next($request);
    }
}
