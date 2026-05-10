<?php

namespace App\View\Components;

use App\Models\Department;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $user = auth()->user();
        if ($user !== null) {
            $user->loadIamRelations();
        }

        $navMode = $user !== null ? $user->institutionalNavMode() : 'department';

        $sidebarDepartments = collect();
        if ($user !== null && in_array($navMode, ['inspection', 'technical_admin'], true)) {
            $sidebarDepartments = Department::query()
                ->where('active', true)
                ->orderBy('code')
                ->get();
        }

        return view('layouts.app', [
            'institutionalNavMode' => $navMode,
            'sidebarDepartments' => $sidebarDepartments,
            'canManageUsers' => $user !== null && $user->canAccessAdministrationMenu(),
            'canManageDepartmentsNav' => $user !== null && $user->canManageDepartments(),
            'canViewExecutiveNav' => $user !== null && $user->canViewExecutiveDashboard(),
            'unreadNotificationsCount' => $user !== null ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
