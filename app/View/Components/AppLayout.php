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

        return view('layouts.app', [
            'sidebarDepartments' => Department::query()
                ->where('active', true)
                ->orderBy('code')
                ->get(),
            'canManageUsers' => $user !== null && $user->canAccessAdministrationMenu(),
            'canManageDepartmentsNav' => $user !== null && $user->canManageDepartments(),
            'canViewExecutiveNav' => $user !== null && $user->canViewExecutiveDashboard(),
        ]);
    }
}
