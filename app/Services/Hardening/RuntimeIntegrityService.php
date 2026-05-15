<?php

namespace App\Services\Hardening;

use App\Models\User;
use Illuminate\Http\Request;

class RuntimeIntegrityService
{
    public function signTransition(User $user, Request $request, string $action, array $context): string
    {
        return app(SecurityAuditService::class)->runtimeActionSigned($user, $request, $action, $context);
    }

    public function verifySignature(string $signature, User $user, Request $request, string $action, array $context): bool
    {
        $expected = app(SecurityAuditService::class)->runtimeActionSigned($user, $request, $action, $context);

        return hash_equals($expected, $signature);
    }

    public function verifyCsrf(Request $request): bool
    {
        return $request->session()->token() !== null;
    }
}
