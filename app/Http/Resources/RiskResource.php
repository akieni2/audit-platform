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
            'actif_id' => $this->actif_id,
            'identified_risk_id' => $this->identified_risk_id,
            'description' => $this->description,
            'impact_inherent' => $this->impact_inherent,
            'probabilite_inherent' => $this->probabilite_inherent,
            'score_inherent' => $this->score_inherent,
            'criticite_inherent' => $this->criticite_inherent,
            'impact_residuel' => $this->impact_residuel,
            'probabilite_residuel' => $this->probabilite_residuel,
            'score_residuel' => $this->score_residuel,
            'criticite_residuel' => $this->criticite_residuel,
            'proprietaire' => $this->proprietaire,
            'departement' => $this->departement,
            'date_revue' => $this->date_revue?->format('Y-m-d'),
            'plan_mitigation' => $this->plan_mitigation,
            'statut_risque' => $this->statut_risque,
            'lifecycle_status' => $this->lifecycle_status,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
