<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Notifications\Enrollment\NewEnrollmentRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'registration_requested_department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('active', true),
            ],
            'fonction' => ['required', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'password' => $request->validated('password'),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'password_expires_at' => now()->addDays((int) config('dgcpt.password_rotation_days', 90)),
            'fonction' => $request->fonction,
            'position' => $request->fonction,
            'matricule' => $request->matricule,
            'registration_requested_department_id' => $deptId,
            'active' => false,
            'approval_status' => 'pending',
            'role_id' => null,
        ]);

        $recipients = User::query()->institutionalSuperAdmins()->get();
        if ($recipients->isEmpty()) {
            $fallback = config('dgcpt.enrollment_alert_email');
            if (is_string($fallback) && $fallback !== '') {
                Notification::route('mail', $fallback)
                    ->notify(new NewEnrollmentRequestNotification($user));
            }
        } else {
            Notification::send($recipients, new NewEnrollmentRequestNotification($user));
        }

        return redirect()->route('login')
            ->with('status', 'Votre demande d\'accès a été transmise à l\'administration DGCPT. Vous recevrez un email après validation de votre compte.');
    }
}
