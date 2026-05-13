<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'risk_uuid' => $this->risk_uuid,
            'risk_reference' => $this->risk_reference,
            'actif_id' => $this->actif_id,
            'identified_risk_id' => $this->identified_risk_id,
            'source_identified_risk_id' => $this->source_identified_risk_id,
            'description' => $this->description,
            'impact_inherent' => $this->impact_inherent,
            'probabilite_inherent' => $this->probabilite_inherent,
            'score_inherent' => $this->score_inherent,
            'inherent_score' => $this->inherent_score,
            'criticite_inherent' => $this->criticite_inherent,
            'impact_residuel' => $this->impact_residuel,
            'probabilite_residuel' => $this->probabilite_residuel,
            'score_residuel' => $this->score_residuel,
            'residual_score' => $this->residual_score,
            'criticite_residuel' => $this->criticite_residuel,
            'criticality' => $this->criticality,
            'heatmap_x' => $this->heatmap_x,
            'heatmap_y' => $this->heatmap_y,
            'proprietaire' => $this->proprietaire,
            'departement' => $this->departement,
            'date_revue' => $this->date_revue?->format('Y-m-d'),
            'plan_mitigation' => $this->plan_mitigation,
            'statut_risque' => $this->statut_risque,
            'lifecycle_status' => $this->lifecycle_status,
            'owner_user_id' => $this->owner_user_id,
            'owner_department_id' => $this->owner_department_id,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'promoted_at' => $this->promoted_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
