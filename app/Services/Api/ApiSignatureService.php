<?php

namespace App\Services\Api;

use Illuminate\Http\Request;

class ApiSignatureService
{
    public function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), (string) config('enterprise_hardening.signing_key'));
    }

    public function verify(Request $request): bool
    {
        if (! config('enterprise_hardening.api_signatures', false)) {
            return true;
        }

        $signature = $request->header('X-Api-Signature');
        $timestamp = $request->header('X-Api-Timestamp');

        if ($signature === null || $timestamp === null) {
            return false;
        }

        $payload = [
            'method' => $request->method(),
            'path' => $request->path(),
            'timestamp' => $timestamp,
            'body' => $request->getContent(),
        ];

        return hash_equals($this->sign($payload), $signature);
    }
}
