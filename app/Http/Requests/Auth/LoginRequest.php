<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Notifications\Iam\AccountLockedNotification;
use App\Notifications\Iam\SuspiciousLoginAttemptNotification;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS_BEFORE_LOCK = 5;

    private const LOCK_DURATION_MINUTES = 15;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = strtolower(trim((string) $this->input('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if ($user->locked_until !== null && $user->locked_until->isFuture()) {
                throw ValidationException::withMessages([
                    'email' => 'Ce compte est temporairement verrouillé après plusieurs tentatives infructueuses.',
                ]);
            }

            if (! $user->active) {
                app(SecurityAuditService::class)->loginFailure($email, $this, 'Compte désactivé');

                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }
        }

        if (! Auth::attempt(['email' => $email, 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            if ($user !== null) {
                $user->increment('failed_login_attempts');
                $user->refresh();

                if ($user->failed_login_attempts === 3) {
                    $user->notify(new SuspiciousLoginAttemptNotification(3));
                }

                if ($user->failed_login_attempts >= self::MAX_ATTEMPTS_BEFORE_LOCK) {
                    $user->forceFill([
                        'locked_until' => now()->addMinutes(self::LOCK_DURATION_MINUTES),
                        'failed_login_attempts' => 0,
                    ])->save();

                    app(SecurityAuditService::class)->accountLocked($user, $this);
                    $user->notify(new AccountLockedNotification);
                }
            }

            app(SecurityAuditService::class)->loginFailure($email, $this);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        /** @var User $authenticated */
        $authenticated = Auth::user();
        $authenticated->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
