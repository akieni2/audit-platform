<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Sonde readiness pour orchestrateurs (K8s, load balancer, supervision).
 */
class HealthController extends Controller
{
    public function ready(): JsonResponse
    {
        $databaseOk = false;

        try {
            DB::connection()->getPdo();
            $databaseOk = true;
        } catch (\Throwable) {
            $databaseOk = false;
        }

        $payload = [
            'status' => $databaseOk ? 'ready' : 'degraded',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'cache_store' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'broadcast_connection' => config('broadcasting.default'),
            'database' => $databaseOk,
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($payload, $databaseOk ? 200 : 503);
    }
}
