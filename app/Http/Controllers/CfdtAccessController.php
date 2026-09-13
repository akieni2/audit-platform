<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Cfdt\CfdtAccountCreatedNotification;
use App\Support\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CfdtAccessController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);

        $users = User::query()
            ->with('department')
            ->where('active', true)
            ->orderBy('name')
            ->orderBy('prenom')
            ->paginate(30);

        $departments = Department::query()->where('active', true)->orderBy('name')->get();

        return view('cfdt.access', compact('users', 'departments'));
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $allowedCfdtRoles = $request->user()->isInstitutionalSuperAdmin()
            ? ['learner', 'trainer', 'validator', 'administrator']
            : ['learner', 'trainer', 'validator'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department_id' => ['required', 'exists:departments,id'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(UserRoles::all())],
            'cfdt_role' => ['required', Rule::in($allowedCfdtRoles)],
        ]);

        $user = User::query()->create([
            ...$data,
            'role_id' => Role::query()->where('slug', $data['role'])->value('id'),
            'password' => Str::random(48),
            'active' => true,
            'must_change_password' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        $token = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        $user->notify(new CfdtAccountCreatedNotification($resetUrl, $this->roleLabel($user->cfdt_role)));
        try {
            $user->notify(new CfdtAccountCreatedNotification($resetUrl, $this->roleLabel($user->cfdt_role), true));
            $status = 'Compte créé et invitation envoyée.';
        } catch (\Throwable $exception) {
            Log::warning('Échec de l’invitation CFDT par courriel.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            $status = 'Compte créé. Le courriel n’a pas pu être envoyé : vérifiez la configuration SMTP.';
        }

        return back()->with('status', $status);
    }

    public function update(Request $request, User $user)
    {
        $this->guard($request);
        abort_if($user->isInstitutionalSuperAdmin(), 403, 'Le compte Super Admin ne peut pas être modifié ici.');

        if (! $request->user()->isInstitutionalSuperAdmin() && $user->cfdt_role === 'administrator') {
            abort(403, 'Seul le Super Admin gère les administrateurs CFDT.');
        }

        $roles = $request->user()->isInstitutionalSuperAdmin()
            ? ['learner', 'trainer', 'validator', 'administrator']
            : ['learner', 'trainer', 'validator'];

        $data = $request->validate([
            'cfdt_role' => ['nullable', Rule::in($roles)],
        ]);

        $user->update(['cfdt_role' => $data['cfdt_role'] ?? null]);

        return back()->with('status', 'Habilitation CFDT mise à jour.');
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()->canAdministerCfdt(), 403);
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'learner' => 'Apprenant',
            'trainer' => 'Formateur',
            'validator' => 'Validateur',
            'administrator' => 'Administrateur CFDT',
            default => 'Sans accès',
        };
    }
}
