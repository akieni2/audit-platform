<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;

interface LlmDriverInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    public function complete(LlmCompletionRequest $request): LlmCompletionResponse;
}
