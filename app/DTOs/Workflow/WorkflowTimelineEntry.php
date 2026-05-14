<?php

namespace App\DTOs\Workflow;

use Carbon\CarbonInterface;

final class WorkflowTimelineEntry
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $source,
        public readonly string $title,
        public readonly ?string $message,
        public readonly string $status,
        public readonly string $tone,
        public readonly CarbonInterface $occurredAt,
        public readonly ?string $actorName = null,
        public readonly ?string $stageName = null,
        public readonly array $payload = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'tone' => $this->tone,
            'occurred_at' => $this->occurredAt,
            'actor_name' => $this->actorName,
            'stage_name' => $this->stageName,
            'payload' => $this->payload,
        ];
    }
}
