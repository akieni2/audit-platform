<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Mission;
use App\Models\Risque;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Console d’administration institutionnelle (IAM, sécurité, synthèse multi-pôles).
 */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAdminDashboard');

        $lockedUsers = User::query()
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderByDesc('locked_until')
            ->limit(15)
            ->get();

        $recentAudits = AuditLog::query()
            ->with('user')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $securityAlerts = AuditLog::query()
            ->whereIn('action', ['login_failure', 'account_locked', 'authorization_denied'])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $recentConnected = User::query()
            ->whereNotNull('last_login_at')
            ->with('department')
            ->orderByDesc('last_login_at')
            ->limit(12)
            ->get(['id', 'name', 'prenom', 'email', 'last_login_at', 'department_id']);

        $usersByDepartment = Department::query()
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderBy('code')
            ->get();

        $missionsByDepartment = Mission::query()
            ->selectRaw('department_id, COUNT(*) as total')
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->with('department')
            ->get();

        $crossDepartmentRisksOpen = Risque::query()
            ->where(function ($q): void {
                $q->where('shared', true)->orWhere('cross_department', true);
            })
            ->count();

        $risquesCritiques = Risque::query()->where('score_residuel', '>=', 16)->count();

        return view('iam.admin.dashboard', [
            'stats' => [
                'active' => User::query()->where('active', true)->count(),
                'inactive' => User::query()->where('active', false)->count(),
                'must_change' => User::query()->where('must_change_password', true)->count(),
                'locked_now' => User::query()
                    ->whereNotNull('locked_until')
                    ->where('locked_until', '>', now())
                    ->count(),
            ],
            'lockedUsers' => $lockedUsers,
            'recentAudits' => $recentAudits,
            'securityAlerts' => $securityAlerts,
            'recentConnected' => $recentConnected,
            'usersByDepartment' => $usersByDepartment,
            'missionsByDepartment' => $missionsByDepartment,
            'crossDepartmentRisksOpen' => $crossDepartmentRisksOpen,
            'risquesCritiques' => $risquesCritiques,
        ]);
    }
}
