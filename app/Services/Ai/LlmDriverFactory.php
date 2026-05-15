<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\LlmDriverInterface;
use InvalidArgumentException;

class LlmDriverFactory
{
    public function driver(?string $name = null): LlmDriverInterface
    {
        $name ??= (string) config('ai_copilot.default_driver', 'stub');
        $config = config('ai_copilot.drivers.'.$name);

        if ($config === null || ! isset($config['class'])) {
            throw new InvalidArgumentException("Driver IA inconnu : {$name}");
        }

        return app($config['class']);
    }
}
