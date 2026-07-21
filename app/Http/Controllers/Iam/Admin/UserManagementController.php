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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $accountView = $request->string('account_view')->toString();

        $query = User::query()
            ->with(['department', 'institutionalRole', 'deletedBy'])
            ->orderBy('name');

        if ($accountView === 'deleted') {
            $query->onlyTrashed();
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', $q)
                    ->orWhere('prenom', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('matricule', 'like', $q)
                    ->orWhere('intercom', 'like', $q);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($accountView !== 'deleted') {
            if ($request->boolean('inactive_only')) {
                $query->where('active', false);
            } elseif ($accountView === 'active') {
                $query->where('active', true);
            } elseif ($accountView === 'inactive') {
                $query->where('active', false);
            } elseif ($request->has('active_filter')) {
                $query->where('active', $request->boolean('active_filter'));
            }
        }

        $users = $query->paginate(25)->withQueryString();

        $stats = [
            'active' => User::query()->where('active', true)->count(),
            'inactive' => User::query()->where('active', false)->count(),
            'deleted' => User::onlyTrashed()->count(),
        ];

        $recentLogins = User::query()
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(8)
            ->get(['id', 'name', 'prenom', 'email', 'last_login_at']);

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
            'filters' => $request->only(['q', 'department_id', 'inactive_only', 'active_filter', 'account_view']),
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
            'name' => $data['nom'],
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'password_changed_at' => now(),
            'must_change_password' => false,
            'password_expires_at' => now()->addDays((int) config('dgcpt.password_rotation_days', 90)),
            'department_id' => $data['department_id'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'position' => $data['position'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'intercom' => $data['intercom'] ?? null,
            'active' => $request->boolean('active'),
            'role' => 'auditeur',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
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

        $beforeRoleId = $user->role_id;
        $beforeDepartmentId = $user->department_id;

        $user->fill([
            'name' => $data['nom'],
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'],
            'department_id' => $data['department_id'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'position' => $data['position'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'intercom' => $data['intercom'] ?? null,
        ]);

        $user->active = $request->boolean('active');

        $user->save();

        $iamChanges = [];
        if ($beforeRoleId !== $user->role_id) {
            $iamChanges['role_id'] = ['from' => $beforeRoleId, 'to' => $user->role_id];
        }
        if ($beforeDepartmentId !== $user->department_id) {
            $iamChanges['department_id'] = ['from' => $beforeDepartmentId, 'to' => $user->department_id];
        }
        if ($iamChanges !== []) {
            app(SecurityAuditService::class)->iamAttributesChanged($request->user(), $user, $request, $iamChanges);
        }

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

    public function generateTemporaryPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'password' => 'Vous ne pouvez pas générer un mot de passe temporaire pour votre propre compte.',
            ]);
        }

        $temporaryPassword = Str::password(20, true, true, true, false);

        DB::transaction(function () use ($request, $user, $temporaryPassword): void {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'password_changed_at' => null,
                'password_expires_at' => null,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $this->revokeUserSessionsAndTokens($user);

            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }

            app(SecurityAuditService::class)->log(
                'temporary_password_generated',
                'iam',
                'Mot de passe temporaire généré — '.$user->email,
                $request->user(),
                $request,
                ['target_user_id' => $user->id],
            );
        });

        return back()->with('temporary_password', [
            'display_name' => $user->displayName(),
            'email' => $user->email,
            'password' => $temporaryPassword,
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deleteFromAdministration', $user);

        if ($request->user()->id === $user->id) {
            return back()->withErrors([
                'delete' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        $protection = app(SuperAdminProtectionService::class);
        if (! $protection->mayDelete($user)) {
            return back()->withErrors([
                'delete' => 'Ce compte ne peut pas être supprimé (protection institutionnelle : dernier super administrateur actif ou compte système).',
            ]);
        }

        $email = $user->email;

        $user->forceFill([
            'active' => false,
            'deleted_by' => $request->user()->id,
        ]);
        $user->save();

        $this->revokeUserSessionsAndTokens($user);

        $user->delete();

        app(SecurityAuditService::class)->userSoftDeleted($request->user(), $user, $request);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Compte supprimé (accès révoqué pour '.$email.' — traces institutionnelles conservées).');
    }

    private function revokeUserSessionsAndTokens(User $user): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->tokens()->delete();
    }
}
