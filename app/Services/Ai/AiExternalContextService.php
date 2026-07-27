<?php

namespace App\Services\Ai;

class AiExternalContextService
{
    /** @var list<string> */
    private array $excludedKeys = [
        'tenant_key',
        'tenant_scope',
    ];

    public function serialize(array $context): string
    {
        $sanitized = $this->sanitize($context);
        $encoded = json_encode(
            $sanitized,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
        );

        if ($encoded === false) {
            return '{}';
        }

        $maxChars = max(1000, (int) config('ai_copilot.context_max_chars', 20000));
        if (mb_strlen($encoded) <= $maxChars) {
            return $encoded;
        }

        return mb_substr($encoded, 0, $maxChars)
            ."\n[Contexte tronqué automatiquement pour limiter les données transmises.]";
    }

    private function sanitize(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return null;
        }

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $childKey => $childValue) {
            $normalizedKey = is_string($childKey) ? $childKey : null;
            if ($normalizedKey !== null && $this->isSensitiveKey($normalizedKey)) {
                continue;
            }

            $sanitized[$childKey] = $this->sanitize($childValue, $normalizedKey);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        if (in_array(strtolower($key), $this->excludedKeys, true)) {
            return true;
        }

        return preg_match('/(?:password|passwd|secret|token|api[_-]?key|credential|authorization)/i', $key) === 1;
    }
}
