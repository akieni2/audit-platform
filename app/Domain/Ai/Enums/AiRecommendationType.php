<?php

namespace App\Domain\Ai\Enums;

enum AiRecommendationType: string
{
    case AuditQuestion = 'audit_question';
    case AuditProgram = 'audit_program';
    case ControlSuggestion = 'control_suggestion';
    case RiskSuggestion = 'risk_suggestion';
    case RiskMitigation = 'risk_mitigation';
    case ComplianceGap = 'compliance_gap';
    case ExecutiveInsight = 'executive_insight';
    case StrategicInsight = 'strategic_insight';
    case RuntimeHint = 'runtime_hint';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::AuditQuestion => 'Question d\'audit',
            self::AuditProgram => 'Programme d\'audit',
            self::ControlSuggestion => 'Suggestion de contrôle',
            self::RiskSuggestion => 'Suggestion de risque',
            self::RiskMitigation => 'Mitigation de risque',
            self::ComplianceGap => 'Écart de conformité',
            self::ExecutiveInsight => 'Insight exécutif',
            self::StrategicInsight => 'Insight stratégique',
            self::RuntimeHint => 'Aide runtime',
            self::General => 'Recommandation générale',
        };
    }
}
