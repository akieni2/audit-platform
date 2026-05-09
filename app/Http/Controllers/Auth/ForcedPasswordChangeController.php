<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\Iam\PasswordChangedNotification;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Changement obligatoire du mot de passe (première connexion ou politique métier).
 */
class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.password-force', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays((int) config('dgcpt.password_rotation_days', 90)),
        ]);

        app(SecurityAuditService::class)->passwordChanged($user, $request);

        $user->notify(new PasswordChangedNotification(true));

        return redirect()
            ->route('dashboard')
            ->with('status', 'Mot de passe mis à jour. Accès à la plateforme débloqué.');
    }
}
