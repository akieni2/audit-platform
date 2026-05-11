<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Notifications\Enrollment\NewEnrollmentRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = Department::query()
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return view('auth.register', compact('departments'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
<<<<<<< HEAD
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'registration_requested_department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],
            'fonction' => ['required', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:100'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);
=======
        $validated = $request->validated();
>>>>>>> 93b781c (server hotfixes and horizon setup)

        $user = User::create([
            'name' => $validated['name'],
            'prenom' => $validated['prenom'],
<<<<<<< HEAD
            'telephone' => $validated['telephone'] ?? null,
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
=======

            'telephone' => $validated['telephone'] ?? null,

            'email' => $validated['email'],

            /*
            |--------------------------------------------------------------------------
            | Le modèle User utilise normalement le cast "hashed"
            | Donc on ne fait PAS Hash::make() ici.
            |--------------------------------------------------------------------------
            */
            'password' => $validated['password'],

>>>>>>> 93b781c (server hotfixes and horizon setup)
            'password_changed_at' => now(),

            'must_change_password' => false,
<<<<<<< HEAD
            'password_expires_at' => now()->addDays((int) config('dgcpt.password_rotation_days', 90)),
            'fonction' => $validated['fonction'],
            'position' => $validated['fonction'],
            'matricule' => $validated['matricule'] ?? null,
            'registration_requested_department_id' => $validated['registration_requested_department_id'],
=======

            'password_expires_at' => now()->addDays(
                (int) config('dgcpt.password_rotation_days', 90)
            ),

            'fonction' => $validated['fonction'],

            'position' => $validated['fonction'],

            'matricule' => $validated['matricule'] ?? null,

            'registration_requested_department_id'
                => $validated['registration_requested_department_id'],

            /*
            |--------------------------------------------------------------------------
            | Compte en attente d’approbation
            |--------------------------------------------------------------------------
            */
>>>>>>> 93b781c (server hotfixes and horizon setup)
            'active' => false,

            'approval_status' => 'pending',

            'role_id' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification Super Admin
        |--------------------------------------------------------------------------
        */
        $recipients = User::query()
            ->institutionalSuperAdmins()
            ->get();

        if ($recipients->isEmpty()) {

            $fallback = config('dgcpt.enrollment_alert_email');

            if (is_string($fallback) && $fallback !== '') {

                Notification::route('mail', $fallback)
                    ->notify(
                        new NewEnrollmentRequestNotification($user)
                    );
            }

        } else {

            Notification::send(
                $recipients,
                new NewEnrollmentRequestNotification($user)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Retour login
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route('login')
            ->with(
                'status',
                'Votre demande d’accès a été transmise à l’administration DGCPT. Vous recevrez un email après validation de votre compte.'
            );
    }
}

