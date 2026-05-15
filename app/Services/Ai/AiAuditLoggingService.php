<?php

namespace App\Services\Ai;

use App\Domain\Ai\Enums\AiExecutionStatus;
use App\Models\AiConversation;
use App\Models\AiExecutionLog;
use App\Models\User;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Dto\LlmCompletionResponse;
use App\Services\Audit\ImmutableAuditTrailService;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Support\Facades\Schema;

class AiAuditLoggingService
{
    public function __construct(
        private TenantIsolationService $tenants,
        private ImmutableAuditTrailService $immutableAudit,
    ) {}

    public function logExecution(
        ?AiConversation $conversation,
        User $user,
        string $driver,
        AiExecutionStatus $status,
        LlmCompletionRequest $request,
        ?LlmCompletionResponse $response = null,
    ): ?AiExecutionLog {
        if (! Schema::hasTable('ai_execution_logs')) {
            return null;
        }

        $tenant = $this->tenants->current($user);

        $log = AiExecutionLog::query()->create([
            'ai_conversation_id' => $conversation?->id,
            'tenant_context_id' => $tenant->tenant?->id,
            'user_id' => $user->id,
            'driver' => $driver,
            'status' => $status->value,
            'prompt_hash' => hash('sha256', json_encode($request->messages)),
            'latency_ms' => $response?->latencyMs,
            'token_estimate' => $response?->tokenEstimate,
            'request_meta' => ['messages_count' => count($request->messages)],
            'response_meta' => $response ? ['confidence' => $response->confidenceScore, 'provenance' => $response->provenance] : null,
            'executed_at' => now(),
        ]);

        if (config('ai_copilot.immutable_audit', true)) {
            $this->immutableAudit->record(
                eventType: 'ai_execution',
                module: 'ai_copilot',
                description: 'Exécution IA — '.$status->value,
                user: $user,
                payload: ['driver' => $driver, 'log_id' => $log->id],
                resourceType: AiExecutionLog::class,
                resourceId: $log->id,
            );
        }

        return $log;
    }
}
