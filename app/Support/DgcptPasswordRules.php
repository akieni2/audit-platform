<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Politique mot de passe institutionnelle DGCPT (min 12, complexité, compromission — OWASP).
 */
class DgcptPasswordRules
{
    public static function defaults(): Password
    {
        $rule = Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols();

        if (! app()->environment('testing')) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }
}
