<?php

namespace App\Services\Runtime;

use Throwable;
use Illuminate\Support\Facades\DB;

final class CoreTransactionRunner
{
    public function __construct(
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
    ) {}

    /**
     * @template TReturn
     *
     * @param  callable(CoreTransactionContext): TReturn  $callback
     * @param  array<string, mixed>  $context
     * @return TReturn
     */
    public function run(string $name, callable $callback, array $context = [], int $attempts = 0): mixed
    {
        $correlationId = $this->events->resolveCorrelationId($context);
        $attempts = max(1, $attempts > 0 ? $attempts : (int) config('core_runtime.transaction_attempts', 1));

        $this->metrics->increment('core_runtime.transaction.started', 1, ['name' => $name]);
        $this->events->record(
            eventName: 'core_runtime.transaction.started',
            payload: ['name' => $name, 'attempts' => $attempts],
            context: $context,
            aggregateType: 'transaction',
            aggregateId: $name,
            correlationId: $correlationId,
            status: 'started',
            idempotencyKey: $name.':'.$correlationId.':started',
        );

        try {
            $result = DB::transaction(function () use ($callback, $name, $correlationId) {
                $transaction = new CoreTransactionContext($name, $correlationId);
                $result = $callback($transaction);
                $transaction->registerAfterCommitCallbacks();

                return $result;
            }, $attempts);

            $this->metrics->increment('core_runtime.transaction.succeeded', 1, ['name' => $name]);
            $this->events->record(
                eventName: 'core_runtime.transaction.succeeded',
                payload: ['name' => $name],
                context: $context,
                aggregateType: 'transaction',
                aggregateId: $name,
                correlationId: $correlationId,
                status: 'succeeded',
                idempotencyKey: $name.':'.$correlationId.':succeeded',
            );

            return $result;
        } catch (Throwable $throwable) {
            $this->metrics->increment('core_runtime.transaction.failed', 1, [
                'name' => $name,
                'exception' => class_basename($throwable),
            ]);
            $this->events->record(
                eventName: 'core_runtime.transaction.failed',
                payload: [
                    'name' => $name,
                    'exception' => class_basename($throwable),
                    'message' => $throwable->getMessage(),
                ],
                context: $context,
                aggregateType: 'transaction',
                aggregateId: $name,
                correlationId: $correlationId,
                status: 'failed',
                idempotencyKey: $name.':'.$correlationId.':failed',
            );

            throw $throwable;
        }
    }
}
