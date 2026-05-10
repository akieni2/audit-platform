<?php

namespace App\Models;

use App\Services\Risk\CriticalityEvaluationService;
use App\Services\Risk\ResidualRiskCalculationService;
use Illuminate\Database\Eloquent\Builder;
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
        'source_department_id',
        'target_department_id',
        'owner_department_id',
        'shared',
        'cross_department',
        'escalated',
        'severity',
        'treatment_plan',
    ];

    protected function casts(): array
    {
        return [
            'date_revue' => 'date',
            'shared' => 'boolean',
            'cross_department' => 'boolean',
            'escalated' => 'boolean',
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

    public function sourceDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'source_department_id');
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }

    /**
     * Risques du dpartement, partags/transverses, ou rattachs  une mission visible.
     *
     * @param  Builder<Risque>  $query
     * @return Builder<Risque>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        $user->loadMissing('institutionalRole');

        if ($user->institutionalRole?->slug === 'copri') {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canViewAllInstitutionalData()) {
            return $query;
        }

        $deptId = $user->department_id;
        if ($deptId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($user, $deptId) {
            $outer->where('owner_department_id', $deptId)
                ->orWhere('source_department_id', $deptId)
                ->orWhere('target_department_id', $deptId)
                ->orWhere(function (Builder $q) use ($deptId) {
                    $q->where('shared', true)
                        ->where(function (Builder $inner) use ($deptId) {
                            $inner->whereNull('target_department_id')
                                ->orWhere('target_department_id', $deptId);
                        });
                })
                ->orWhereHas(
                    'actif.processus.mission',
                    fn (Builder $mq) => $mq->visibleToUser($user)
                );
        });
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
