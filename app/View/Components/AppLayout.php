<?php

namespace App\View\Components;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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

        $canManageEnrollments = $user !== null && Gate::forUser($user)->allows('manageEnrollmentRequests');
        $pendingEnrollmentsCount = $canManageEnrollments
            ? User::query()->where('approval_status', User::APPROVAL_STATUS_PENDING)->count()
            : 0;

        return view('layouts.app', [
            'institutionalNavMode' => $navMode,
            'sidebarDepartments' => $sidebarDepartments,
            'canManageUsers' => $user !== null && ($user->canAccessAdministrationMenu() || $user->canManageDepartmentUsers()),
            'canManageDepartmentsNav' => $user !== null && $user->canManageDepartments(),
            'canViewOrganizationChartNav' => $user !== null && $user->canAccessOrganizationChart(),
            'canViewExecutiveNav' => $user !== null && $user->canViewExecutiveDashboard(),
            'unreadNotificationsCount' => $user !== null ? $user->unreadNotifications()->count() : 0,
            'canManageEnrollments' => $canManageEnrollments,
            'pendingEnrollmentsCount' => $pendingEnrollmentsCount,
        ]);
    }
}
