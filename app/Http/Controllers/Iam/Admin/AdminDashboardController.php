<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord administration centrale (statistiques et alertes sécurité).
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
            ->whereIn('action', ['login_failure', 'account_locked'])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

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
        ]);
    }
}
