<?php

return [
    'enabled' => (bool) env('AI_COPILOT_ENABLED', true),
    'default_driver' => env('AI_COPILOT_DRIVER', 'stub'),
    'assistive_only' => true,
    'auto_execute_recommendations' => false,
    'auto_validate_workflow' => false,
    'max_tokens' => (int) env('AI_COPILOT_MAX_TOKENS', 2048),
    'confidence_threshold' => (float) env('AI_COPILOT_CONFIDENCE_THRESHOLD', 0.65),
    'tenant_isolation' => (bool) env('AI_COPILOT_TENANT_ISOLATION', true),
    'immutable_audit' => (bool) env('AI_COPILOT_IMMUTABLE_AUDIT', true),
    'drivers' => [
        'stub' => [
            'class' => \App\Services\Ai\Drivers\StubLlmDriver::class,
        ],
        'openai' => [
            'class' => \App\Services\Ai\Drivers\OpenAiLlmDriver::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        ],
        'ollama' => [
            'class' => \App\Services\Ai\Drivers\OllamaLlmDriver::class,
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3'),
        ],
        'azure_openai' => [
            'class' => \App\Services\Ai\Drivers\AzureOpenAiLlmDriver::class,
            'api_key' => env('AZURE_OPENAI_API_KEY'),
            'endpoint' => env('AZURE_OPENAI_ENDPOINT'),
            'deployment' => env('AZURE_OPENAI_DEPLOYMENT'),
        ],
    ],
    'frameworks' => ['ISO27001', 'COSO', 'COBIT', 'ITIL', 'DGCPT'],
];
