<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consultation du journal de sécurité (traçabilité institutionnelle).
 */
class SecurityAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewSecurityAuditLog');

        $query = AuditLog::query()->with('user')->orderByDesc('id');

        if ($request->filled('module')) {
            $query->where('module', $request->string('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        $logs = $query->paginate(40)->withQueryString();

        return view('iam.admin.audit-logs', [
            'logs' => $logs,
            'filters' => $request->only(['module', 'action']),
        ]);
    }
}
