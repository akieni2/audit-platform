<?php

return [
    'tenant_isolation' => (bool) env('ENTERPRISE_TENANT_ISOLATION', true),
    'immutable_audit' => (bool) env('ENTERPRISE_IMMUTABLE_AUDIT', true),
    'signed_runtime_actions' => (bool) env('ENTERPRISE_SIGNED_RUNTIME', true),
    'api_signatures' => (bool) env('ENTERPRISE_API_SIGNATURES', false),
    'realtime_broadcast' => (bool) env('ENTERPRISE_REALTIME_BROADCAST', false),
    'runtime_cache_ttl' => (int) env('ENTERPRISE_RUNTIME_CACHE_TTL', 300),
    'analytics_cache_ttl' => (int) env('ENTERPRISE_ANALYTICS_CACHE_TTL', 600),
    'projection_queue' => env('ENTERPRISE_PROJECTION_QUEUE', env('CORE_RUNTIME_PROJECTION_QUEUE', 'projections')),
    'dead_letter_queue' => env('ENTERPRISE_DEAD_LETTER_QUEUE', 'dead-letter'),
    'encryption_key' => env('ENTERPRISE_PAYLOAD_KEY'),
    'signing_key' => env('ENTERPRISE_SIGNING_KEY', env('APP_KEY')),
];
