<?php

namespace App\Models;

use App\Services\Risk\CriticalityEvaluationService;
use App\Services\Risk\ResidualRiskCalculationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Risque extends Model
{
    protected $fillable = [
        'actif_id',
        'description',
        'impact_inherent',
        'probabilite_inherent',
        'score_inherent',
        'impact_residuel',
        'probabilite_residuel',
        'score_residuel',
        'niveau',
        'proprietaire',
        'departement',
        'date_revue',
        'plan_mitigation',
        'statut_risque',
        'criticite_inherent',
        'criticite_residuel',
    ];

    protected function casts(): array
    {
        return [
            'date_revue' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Risque $risque): void {
            $risque->score_inherent = (int) $risque->impact_inherent * (int) $risque->probabilite_inherent;
            $evaluator = app(CriticalityEvaluationService::class);
            $risque->criticite_inherent = $evaluator->levelFromScore($risque->score_inherent)->value;
        });
    }

    public function actif(): BelongsTo
    {
        return $this->belongsTo(Actif::class);
    }

    public function controles(): HasMany
    {
        return $this->hasMany(Controle::class);
    }

    public function actionsCorrectives(): HasMany
    {
        return $this->hasMany(ActionCorrective::class);
    }

    public function calculerRisqueResiduel(): void
    {
        app(ResidualRiskCalculationService::class)->apply($this);
    }

    /*
    |--------------------------------------------------
    | MOTEUR AUTOMATIQUE PLAN D'ACTION
    |--------------------------------------------------
    */

    public function genererPlanActionAutomatique(): void
    {
        if (($this->score_residuel ?? 0) < 10) {
            return;
        }

        if ($this->actionsCorrectives()->count() > 0) {
            return;
        }

        $recommendations = RecommendationLibrary::all();

        foreach ($recommendations as $rec) {
            ActionCorrective::create([
                'risque_id' => $this->id,
                'description' => $rec->description,
                'responsable' => 'À définir',
                'date_echeance' => now()->addDays(
                    ($this->score_residuel ?? 0) >= 16 ? 7 : 30
                ),
                'statut' => 'ouvert',
            ]);
        }
    }
}
