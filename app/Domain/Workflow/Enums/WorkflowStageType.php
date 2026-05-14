<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowStageType: string
{
    case Mission = 'mission';
    case ServiceSelection = 'service_selection';
    case Questionnaire = 'questionnaire';
    case Form = 'form';
    case RiskCapture = 'risk_capture';
    case Heatmap = 'heatmap';
    case DocumentReview = 'document_review';
    case Approval = 'approval';
    case ActionPlan = 'action_plan';
    case Reporting = 'reporting';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Mission => 'Mission',
            self::ServiceSelection => 'Services',
            self::Questionnaire => 'Questionnaires',
            self::Form => 'Formulaire',
            self::RiskCapture => 'Capture de risque',
            self::Heatmap => 'Cartographie',
            self::DocumentReview => 'Revue documentaire',
            self::Approval => 'Approbation',
            self::ActionPlan => 'Plan d’action',
            self::Reporting => 'Reporting',
            self::Custom => 'Personnalisé',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $type) {
            $labels[$type->value] = $type->label();
        }

        return $labels;
    }

    public static function fromMixed(string|null $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        $legacy = [
            'mission_context' => self::Mission,
            'mission' => self::Mission,
            'service_selection' => self::ServiceSelection,
            'entretien' => self::Questionnaire,
            'questionnaire' => self::Questionnaire,
            'form' => self::Form,
            'risk_identification' => self::RiskCapture,
            'risk_review' => self::RiskCapture,
            'risk_validation' => self::RiskCapture,
            'risk_capture' => self::RiskCapture,
            'heatmap' => self::Heatmap,
            'document_review' => self::DocumentReview,
            'approval' => self::Approval,
            'action_plan' => self::ActionPlan,
            'corrective_action' => self::ActionPlan,
            'reporting' => self::Reporting,
            'signature' => self::Approval,
            'archive' => self::Custom,
            'custom' => self::Custom,
        ];

        return $legacy[$normalized] ?? self::tryFrom($normalized);
    }
}
