<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowStageType: string
{
    case MissionContext = 'mission_context';
    case ServiceSelection = 'service_selection';
    case Entretien = 'entretien';
    case Questionnaire = 'questionnaire';
    case RiskIdentification = 'risk_identification';
    case RiskReview = 'risk_review';
    case RiskValidation = 'risk_validation';
    case Heatmap = 'heatmap';
    case ActionPlan = 'action_plan';
    case CorrectiveAction = 'corrective_action';
    case Reporting = 'reporting';
    case Approval = 'approval';
    case Signature = 'signature';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::MissionContext => 'Mission',
            self::ServiceSelection => 'Services',
            self::Entretien => 'Entretiens',
            self::Questionnaire => 'Questionnaires',
            self::RiskIdentification => 'Identification des risques',
            self::RiskReview => 'Revue des risques',
            self::RiskValidation => 'Validation des risques',
            self::Heatmap => 'Cartographie',
            self::ActionPlan => 'Plan d’action',
            self::CorrectiveAction => 'Actions correctives',
            self::Reporting => 'Reporting',
            self::Approval => 'Approbation',
            self::Signature => 'Signature',
            self::Archive => 'Archivage',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
