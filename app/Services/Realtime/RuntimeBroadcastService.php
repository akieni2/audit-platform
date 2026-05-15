<?php

namespace App\Services\Realtime;

class RuntimeBroadcastService
{
    public function channelForMission(int $missionId): string
    {
        return 'mission.'.$missionId.'.runtime';
    }

    public function shouldBroadcast(): bool
    {
        return (bool) config('enterprise_hardening.realtime_broadcast', false);
    }

    public function payload(string $event, array $data): array
    {
        return [
            'event' => $event,
            'data' => $data,
            'broadcast' => $this->shouldBroadcast(),
            'fallback' => 'polling',
        ];
    }
}
