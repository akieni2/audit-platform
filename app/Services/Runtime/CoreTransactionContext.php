<?php

namespace App\Services\Runtime;

final class CoreTransactionContext
{
    /** @var list<callable(): void> */
    private array $afterCommitCallbacks = [];

    public function __construct(
        public readonly string $name,
        public readonly string $correlationId,
    ) {}

    public function afterCommit(callable $callback): void
    {
        $this->afterCommitCallbacks[] = $callback;
    }

    public function registerAfterCommitCallbacks(): void
    {
        foreach ($this->afterCommitCallbacks as $callback) {
            \Illuminate\Support\Facades\DB::afterCommit($callback);
        }
    }
}
