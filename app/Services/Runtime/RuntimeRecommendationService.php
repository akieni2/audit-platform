<?php

namespace App\Services\Runtime;

use App\Models\MethodologyTemplate;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Methodologies\MethodologyWorkflowMappingService;

class RuntimeRecommendationService
{
    public function __construct(
        private RiskSuggestionService $riskSuggestions,
        private IntelligentScoringService $scoring,
        private MethodologyWorkflowMappingService $methodologyMappings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStage(WorkflowInstance $instance, ?WorkflowStage $stage = null): array
    {
        $instance->loadMissing(['mission.department', 'workflowTemplate.methodologyTemplate', 'currentStage']);
        $stage ??= $instance->currentStage;

        $methodology = $instance->workflowTemplate?->methodologyTemplate
            ?? MethodologyTemplate::query()->where('department_id', $instance->mission?->department_id)->latest('id')->first();

        $mappingStack = $methodology instanceof MethodologyTemplate
            ? $this->methodologyMappings->resolveStack($methodology, $instance->mission?->department_id)
            : ['risk_categories' => [], 'control_measures' => collect()];

        $riskCategory = $mappingStack['risk_categories'][0] ?? $stage?->code ?? 'general';
        $riskSuggestions = $this->riskSuggestions->suggestForMission($instance->mission, $stage, [
            'risk_category' => $riskCategory,
        ]);

        $score = $this->scoring->scoreRiskPayload([
            'probability' => data_get($instance->metadata, 'latest_probability', 3),
            'impact' => data_get($instance->metadata, 'latest_impact', 3),
            'criticality' => data_get($instance->metadata, 'latest_criticality', 'medium'),
            'controls' => count($riskSuggestions['recommendations'] ?? []),
            'mitigation' => data_get($instance->metadata, 'mitigation_progress', 0.25),
        ]);

        return [
            'methodology' => $methodology,
            'mapping_stack' => $mappingStack,
            'risk_suggestions' => $riskSuggestions,
            'intelligent_score' => $score,
            'next_actions' => [
                'Valider les contrôles recommandés',
                'Réviser le mapping méthodologique du stage',
                'Confirmer la taxonomie risque avant promotion',
            ],
        ];
    }
}
