<?php

namespace App\Services\Runtime;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $projectionQueue = (string) config('enterprise_hardening.projection_queue', 'projections');
        $deadLetter = (string) config('enterprise_hardening.dead_letter_queue', 'dead-letter');

        $pending = 0;
        $failed = 0;

        if (Schema::hasTable('jobs')) {
            $pending = (int) DB::table('jobs')->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')->count();
        }

        return [
            'connection' => config('queue.default'),
            'projection_queue' => $projectionQueue,
            'dead_letter_queue' => $deadLetter,
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'healthy' => $failed < 25,
        ];
    }
}
