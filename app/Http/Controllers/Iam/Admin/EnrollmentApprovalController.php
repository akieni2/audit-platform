<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveEnrollmentRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Enrollment\AccountApprovedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', User::APPROVAL_STATUS_PENDING);
        if (! in_array($status, [User::APPROVAL_STATUS_PENDING, User::APPROVAL_STATUS_REJECTED, 'all'], true)) {
            $status = User::APPROVAL_STATUS_PENDING;
        }

        $query = User::query()
            ->with(['registrationRequestedDepartment', 'institutionalRole', 'department'])
            ->orderByDesc('created_at');

        if ($status === User::APPROVAL_STATUS_PENDING) {
            $query->where('approval_status', User::APPROVAL_STATUS_PENDING);
        } elseif ($status === User::APPROVAL_STATUS_REJECTED) {
            $query->where('approval_status', User::APPROVAL_STATUS_REJECTED);
        } else {
            $query->whereIn('approval_status', [User::APPROVAL_STATUS_PENDING, User::APPROVAL_STATUS_REJECTED]);
        }

        $users = $query->paginate(25)->withQueryString();

        return view('iam.admin.enrollments.index', compact('users', 'status'));
    }

    public function pendingCount(): JsonResponse
    {
        $count = User::query()->where('approval_status', User::APPROVAL_STATUS_PENDING)->count();

        return response()->json(['count' => $count]);
    }

    public function review(User $user): View|RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return redirect()->route('admin.enrollments.index')
                ->withErrors(['user' => 'Cette demande ne peut plus être traitée (déjà traitée ou inexistante).']);
        }

        $roles = Role::query()->where('active', true)->orderBy('hierarchy_level')->get();
        $departments = Department::query()->where('active', true)->orderBy('code')->get();

        return view('iam.admin.enrollments.review', compact('user', 'roles', 'departments'));
    }

    public function approve(ApproveEnrollmentRequest $request, User $user): RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return redirect()->route('admin.enrollments.index')
                ->withErrors(['user' => 'Cette demande ne peut plus être approuvée.']);
        }

        $user->update([
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'role_id' => $request->validated('role_id'),
            'department_id' => $request->validated('department_id'),
        ]);

        $user->notify(new AccountApprovedNotification);

        return redirect()->route('admin.enrollments.index', ['status' => User::APPROVAL_STATUS_PENDING])
            ->with('status', 'Compte approuvé et activé. Un email a été envoyé au demandeur.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        if (! $user->isPendingApproval()) {
            return redirect()->route('admin.enrollments.index')
                ->withErrors(['user' => 'Cette demande ne peut plus être rejetée.']);
        }

        $user->update([
            'active' => false,
            'approval_status' => User::APPROVAL_STATUS_REJECTED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.enrollments.index', ['status' => User::APPROVAL_STATUS_REJECTED])
            ->with('status', 'Demande rejetée.');
    }
}
