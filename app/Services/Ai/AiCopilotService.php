<?php

namespace App\Services\Ai;

use App\Domain\Ai\Enums\AiConfidenceLevel;
use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiExecutionStatus;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\AiAnalysisSnapshot;
use App\Models\AiConversation;
use App\Models\AiRecommendation;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\Dto\LlmCompletionRequest;
use App\Services\Ai\Governance\AiGovernanceService;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Support\Facades\Schema;

class AiCopilotService
{
    public function __construct(
        private LlmDriverFactory $drivers,
        private AiContextBuilderService $contextBuilder,
        private AiPromptEngineService $promptEngine,
        private AiResponseSanitizerService $sanitizer,
        private AiAuditLoggingService $auditLogging,
        private AiGovernanceService $governance,
        private TenantIsolationService $tenants,
    ) {}

    /**
     * @return array{conversation: AiConversation, response: string, recommendation: ?AiRecommendation}
     */
    public function assist(
        Mission $mission,
        User $user,
        AiContextType $contextType,
        string $userPrompt,
        AiRecommendationType $recommendationType = AiRecommendationType::General,
        array $extraContext = [],
    ): array {
        $this->governance->assertAssistiveRequestAllowed($user, $mission);
        $this->tenants->assertMissionAccess($mission, $user);

        $context = $this->contextBuilder->build($mission, $user, $contextType, $extraContext);
        $conversation = $this->startConversation($mission, $user, $contextType, $context);

        $systemPrompt = $this->promptEngine->resolveSystemPrompt($contextType, $mission->department_id);
        $request = new LlmCompletionRequest(
            messages: [['role' => 'user', 'content' => $userPrompt]],
            systemPrompt: $systemPrompt,
            maxTokens: (int) config('ai_copilot.max_tokens', 2048),
            metadata: $context,
        );

        $driver = $this->drivers->driver();
        $this->auditLogging->logExecution($conversation, $user, $driver->name(), AiExecutionStatus::Running, $request);

        $raw = $driver->complete($request);
        $content = $this->sanitizer->sanitize($raw->content);
        $confidence = AiConfidenceLevel::fromScore($raw->confidenceScore);

        $this->auditLogging->logExecution($conversation, $user, $driver->name(), AiExecutionStatus::Completed, $request, $raw);

        $recommendation = $this->persistRecommendation(
            $conversation,
            $mission,
            $user,
            $recommendationType,
            $confidence,
            $content,
            $raw->provenance,
        );

        $this->captureSnapshot($conversation, $mission, $contextType, $context, $content, $confidence, $driver->name());

        return [
            'conversation' => $conversation,
            'response' => $content,
            'recommendation' => $recommendation,
        ];
    }

    private function startConversation(Mission $mission, User $user, AiContextType $type, array $context): AiConversation
    {
        $tenant = $this->tenants->current($user);

        return AiConversation::query()->create([
            'tenant_context_id' => $tenant->tenant?->id,
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'context_type' => $type->value,
            'title' => 'Copilote — '.$mission->organisation,
            'status' => 'active',
            'context_payload' => $context,
        ]);
    }

    private function persistRecommendation(
        AiConversation $conversation,
        Mission $mission,
        User $user,
        AiRecommendationType $type,
        AiConfidenceLevel $confidence,
        string $summary,
        array $provenance,
    ): ?AiRecommendation {
        if (! Schema::hasTable('ai_recommendations')) {
            return null;
        }

        $tenant = $this->tenants->current($user);

        return AiRecommendation::query()->create([
            'ai_conversation_id' => $conversation->id,
            'tenant_context_id' => $tenant->tenant?->id,
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'recommendation_type' => $type->value,
            'confidence_level' => $confidence->value,
            'title' => $type->label(),
            'summary' => $summary,
            'rationale' => 'Suggestion assistive — non contraignante.',
            'payload' => ['provenance' => $provenance],
            'requires_human_validation' => true,
        ]);
    }

    private function captureSnapshot(
        AiConversation $conversation,
        Mission $mission,
        AiContextType $type,
        array $input,
        string $output,
        AiConfidenceLevel $confidence,
        string $driver,
    ): void {
        if (! Schema::hasTable('ai_analysis_snapshots')) {
            return;
        }

        $body = ['input' => $input, 'output' => $output, 'captured_at' => now()->toIso8601String()];

        AiAnalysisSnapshot::query()->create([
            'ai_conversation_id' => $conversation->id,
            'mission_id' => $mission->id,
            'context_type' => $type->value,
            'analysis_scope' => 'assistive',
            'input_snapshot' => $input,
            'output_snapshot' => ['content' => $output],
            'confidence_level' => $confidence->value,
            'driver' => $driver,
            'integrity_hash' => hash('sha256', json_encode($body)),
            'captured_at' => now(),
        ]);
    }
}
