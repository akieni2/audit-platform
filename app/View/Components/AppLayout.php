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
            $user->loadMissing(['institutionalRole', 'institutionalRole.permissions']);
        }

        return view('layouts.app', [
            'sidebarDepartments' => Department::query()
                ->where('active', true)
                ->orderBy('code')
                ->get(),
            'canManageUsers' => $user !== null && $user->canAccessAdministrationMenu(),
        ]);
    }
}
