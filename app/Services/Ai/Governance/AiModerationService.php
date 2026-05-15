<?php

namespace App\Services\Ai\Governance;

class AiModerationService
{
    /** @var list<string> */
    private array $denyPatterns = [
        'contourner',
        'bypass',
        'sans audit',
        'auto approve',
    ];

    public function moderate(string $prompt): array
    {
        $flags = [];
        $allowed = true;

        foreach ($this->denyPatterns as $pattern) {
            if (str_contains(strtolower($prompt), strtolower($pattern))) {
                $flags[] = $pattern;
                $allowed = false;
            }
        }

        return ['allowed' => $allowed, 'flags' => $flags];
    }

    public function moderatePrompt(string $prompt): array
    {
        return $this->moderate($prompt);
    }
}
