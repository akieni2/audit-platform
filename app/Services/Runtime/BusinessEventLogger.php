<?php

namespace App\Services\Runtime;

use App\Models\BusinessEvent;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BusinessEventLogger
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $eventName,
        array $payload = [],
        array $context = [],
        ?string $aggregateType = null,
        int|string|null $aggregateId = null,
        ?User $actor = null,
        ?string $status = 'recorded',
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        ?string $queue = null,
        ?int $missionId = null,
    ): BusinessEvent {
        $correlationId ??= $this->resolveCorrelationId($context);
        $recordedAt = now();

        $event = new BusinessEvent();

        if (Schema::hasTable('business_events')) {
            $event = $idempotencyKey !== null
                ? BusinessEvent::query()->firstOrNew([
                    'event_name' => $eventName,
                    'idempotency_key' => $idempotencyKey,
                ])
                : new BusinessEvent();
            $event->fill([
                'event_name' => $eventName,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId !== null ? (string) $aggregateId : null,
                'mission_id' => $missionId,
                'actor_user_id' => $actor?->id,
                'correlation_id' => $correlationId,
                'causation_id' => isset($context['causation_id']) ? (string) $context['causation_id'] : null,
                'idempotency_key' => $idempotencyKey,
                'status' => $status ?? 'recorded',
                'queue' => $queue,
                'payload' => $payload,
                'context' => $context,
                'occurred_at' => $recordedAt,
            ]);
            $event->save();
        }

        Log::channel((string) config('core_runtime.business_log_channel', 'stack'))->info($eventName, [
            'business_event_id' => $event->id,
            'event_name' => $eventName,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'mission_id' => $missionId,
            'actor_user_id' => $actor?->id,
            'correlation_id' => $correlationId,
            'status' => $status,
            'queue' => $queue,
            'payload' => $payload,
            'context' => $context,
            'occurred_at' => $recordedAt->toIso8601String(),
        ]);

        return $event;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function resolveCorrelationId(array $context = []): string
    {
        $candidate = $context['correlation_id']
            ?? $context['request_id']
            ?? request()?->header('X-Request-Id');

        return is_string($candidate) && trim($candidate) !== ''
            ? trim($candidate)
            : (string) Str::uuid();
    }
}
