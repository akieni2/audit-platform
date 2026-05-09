<?php

namespace App\Http\Controllers\Iam\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\Iam\SecurityAuditService;
use App\Services\Iam\SuperAdminProtectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['department', 'institutionalRole'])
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('matricule', 'like', $q);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->boolean('inactive_only')) {
            $query->where('active', false);
        } elseif ($request->has('active_filter')) {
            $query->where('active', $request->boolean('active_filter'));
        }

        $users = $query->paginate(25)->withQueryString();

        $stats = [
            'active' => User::query()->where('active', true)->count(),
            'inactive' => User::query()->where('active', false)->count(),
        ];

        $recentLogins = User::query()
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(8)
            ->get(['id', 'name', 'email', 'last_login_at']);

        $byDepartment = Department::query()
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderBy('code')
            ->get();

        $byRole = Role::query()
            ->withCount(['users' => fn ($q) => $q->where('active', true)])
            ->orderByDesc('hierarchy_level')
            ->get();

        return view('iam.admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'recentLogins' => $recentLogins,
            'byDepartment' => $byDepartment,
            'byRole' => $byRole,
            'departments' => Department::query()->where('active', true)->orderBy('code')->get(),
            'roles' => Role::query()->where('active', true)->orderByDesc('hierarchy_level')->get(),
            'filters' => $request->only(['q', 'department_id', 'inactive_only', 'active_filter']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('iam.admin.users.create', [
            'departments' => Department::query()->where('active', true)->orderBy('code')->get(),
            'roles' => Role::query()->where('active', true)->orderByDesc('hierarchy_level')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'password_expires_at' => now()->addDays((int) config('dgcpt.password_rotation_days', 90)),
            'department_id' => $data['department_id'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'position' => $data['position'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'active' => $request->boolean('active'),
            'role' => 'auditeur',
        ]);

        app(SecurityAuditService::class)->userCreated($request->user(), $user, $request);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur créé : '.$user->email);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('iam.admin.users.edit', [
            'editUser' => $user->load(['department', 'institutionalRole']),
            'departments' => Department::query()->where('active', true)->orderBy('code')->get(),
            'roles' => Role::query()->where('active', true)->orderByDesc('hierarchy_level')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $protection = app(SuperAdminProtectionService::class);

        $newRoleId = $data['role_id'] ?? null;
        if ($user->institutionalRole?->slug === 'super_admin') {
            $newRole = $newRoleId ? Role::query()->find((int) $newRoleId) : null;
            if (($newRole === null || $newRole->slug !== 'super_admin') && ! $protection->mayRemoveSuperAdminRole($user)) {
                return back()->withErrors([
                    'role_id' => 'Impossible de retirer le rôle super administrateur pour ce compte.',
                ]);
            }
        }

        if ($user->active && ! $request->boolean('active') && ! $protection->mayDeactivate($user)) {
            return back()->withErrors([
                'active' => 'Ce compte ne peut pas être désactivé (protection institutionnelle).',
            ]);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'department_id' => $data['department_id'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'position' => $data['position'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'telephone' => $data['telephone'] ?? null,
        ]);

        $user->active = $request->boolean('active');

        $user->save();

        app(SecurityAuditService::class)->userUpdated($request->user(), $user, $request);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Utilisateur enregistré.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($request->user()->id === $user->id) {
            return back()->withErrors(['active' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $protection = app(SuperAdminProtectionService::class);
        if (! $protection->mayDeactivate($user)) {
            return back()->withErrors([
                'active' => 'Ce compte ne peut pas être désactivé (protection institutionnelle).',
            ]);
        }

        $user->update(['active' => false]);

        app(SecurityAuditService::class)->userDeactivated($request->user(), $user, $request);

        return back()->with('status', 'Utilisateur désactivé.');
    }

    public function sendPasswordReset(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)]);
        }

        app(SecurityAuditService::class)->log(
            'password_reset_requested',
            'iam',
            'Lien de réinitialisation envoyé — '.$user->email,
            $request->user(),
            $request,
            ['target_user_id' => $user->id],
        );

        return back()->with('status', 'Lien de réinitialisation envoyé à '.$user->email.'.');
    }
}
