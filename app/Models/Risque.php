<?php

namespace App\Models;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Services\Risk\MissionRiskProjectionService;
use App\Services\Risk\ResidualRiskCalculationService;
use App\Services\Risk\RiskScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Risque extends Model
{
    protected $fillable = [
        'actif_id',
        'identified_risk_id',
        'source_identified_risk_id',
        'source_entretien_id',
        'source_question_id',
        'description',
        'risk_uuid',
        'risk_reference',
        'promotion_signature',
        'impact_inherent',
        'probabilite_inherent',
        'score_inherent',
        'inherent_score',
        'impact_residuel',
        'probabilite_residuel',
        'score_residuel',
        'residual_score',
        'niveau',
        'proprietaire',
        'departement',
        'date_revue',
        'detected_at',
        'reviewed_at',
        'promoted_at',
        'closed_at',
        'archived_at',
        'plan_mitigation',
        'statut_risque',
        'lifecycle_status',
        'criticality',
        'heatmap_x',
        'heatmap_y',
        'owner_user_id',
        'reviewed_by',
        'promoted_by',
        'approval_notes',
        'closure_notes',
        'metadata',
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
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'promoted_at' => 'datetime',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
            'metadata' => 'array',
            'shared' => 'boolean',
            'cross_department' => 'boolean',
            'escalated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Risque $risque): void {
            $scoring = app(RiskScoringService::class);
            $package = $scoring->packageInherent(
                $risque->probabilite_inherent,
                $risque->impact_inherent,
                $risque->criticite_inherent,
            );

            $risque->probabilite_inherent = $package['probability'];
            $risque->impact_inherent = $package['impact'];
            $risque->score_inherent = $package['score'];
            $risque->inherent_score = $package['score'];
            $risque->criticite_inherent = $package['criticality'];
            $risque->criticality ??= $risque->criticite_residuel ?: $package['criticality'];
            $risque->heatmap_x ??= $package['heatmap_x'];
            $risque->heatmap_y ??= $package['heatmap_y'];
            $risque->lifecycle_status ??= RiskLifecycleStatus::Promoted->value;
        });
    }

    public function actif(): BelongsTo
    {
        return $this->belongsTo(Actif::class);
    }

    public function identifiedRisk(): BelongsTo
    {
        return $this->belongsTo(IdentifiedRisk::class);
    }

    public function sourceIdentifiedRisk(): BelongsTo
    {
        return $this->belongsTo(IdentifiedRisk::class, 'source_identified_risk_id');
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by')->withTrashed();
    }

    public function getMissionIdAttribute(): ?int
    {
        $this->loadMissing('actif.processus');

        return $this->actif?->processus?->mission_id;
    }

    public function lifecycleLabel(): string
    {
        return RiskLifecycleStatus::fromMixed($this->lifecycle_status)->label();
    }

    public function criticalityLabel(): ?string
    {
        return CriticalityLevel::fromMixed($this->criticality ?: $this->criticite_residuel ?: $this->criticite_inherent)?->label();
    }

    /**
     * Risques du d?partement, partag?s/transverses, ou rattach?s ? une mission visible.
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

        $this->loadMissing('actif.processus.mission');
        $missionId = $this->actif?->processus?->mission_id;
        if ($missionId !== null) {
            app(MissionRiskProjectionService::class)->refreshForMissionId((int) $missionId);
        }
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
                'responsable' => '? d?finir',
                'date_echeance' => now()->addDays(
                    $this->criticite_residuel === CriticalityLevel::Critical->value ? 7 : 30
                ),
                'statut' => 'ouvert',
            ]);
        }
    }
}
