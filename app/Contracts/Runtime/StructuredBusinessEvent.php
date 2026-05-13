<?php

namespace App\Contracts\Runtime;

interface StructuredBusinessEvent
{
    public function eventName(): string;

    public function aggregateType(): string;

    public function aggregateId(): int|string|null;

    /**
     * @return array<string, mixed>
     */
    public function context(): array;
}
