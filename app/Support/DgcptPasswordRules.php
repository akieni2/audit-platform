<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Politique mot de passe DGCPT, pilotée par config/dgcpt.php (durcissement possible via .env).
 */
class DgcptPasswordRules
{
    public static function defaults(): Password
    {
        $min = max(1, (int) config('dgcpt.password_min_length', 8));

        $rule = Password::min($min);

        if (config('dgcpt.password_require_mixed_case', false)) {
            $rule = $rule->mixedCase();
        }

        if (config('dgcpt.password_require_numbers', false)) {
            $rule = $rule->numbers();
        }

        if (config('dgcpt.password_require_symbols', false)) {
            $rule = $rule->symbols();
        }

        if (! app()->environment('testing') && config('dgcpt.password_uncompromised', false)) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }
}
