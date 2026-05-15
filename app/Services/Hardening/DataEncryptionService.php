<?php

namespace App\Services\Hardening;

use Illuminate\Support\Facades\Crypt;

class DataEncryptionService
{
    public function encryptPayload(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload));
    }

    public function decryptPayload(string $encrypted): array
    {
        $decoded = json_decode(Crypt::decryptString($encrypted), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function hashSensitive(string $value): string
    {
        $key = (string) config('enterprise_hardening.encryption_key', config('app.key'));

        return hash_hmac('sha256', $value, $key);
    }
}
