<?php

return [
    'business_log_channel' => env('BUSINESS_LOG_CHANNEL', 'business'),
    'transaction_attempts' => (int) env('CORE_RUNTIME_TRANSACTION_ATTEMPTS', 1),
    'projection_queue' => env('CORE_RUNTIME_PROJECTION_QUEUE', 'projections'),
    'projection_queue_tries' => (int) env('CORE_RUNTIME_PROJECTION_QUEUE_TRIES', 3),
    'async_projection_refresh' => (bool) env('CORE_RUNTIME_ASYNC_PROJECTION_REFRESH', true),
    'projection_integrity_auto_repair' => (bool) env('CORE_RUNTIME_PROJECTION_AUTO_REPAIR', true),
];
