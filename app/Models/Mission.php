<?php

namespace App\Models;

use App\Domain\Risk\Enums\CriticalityLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mission extends Model
{
    use SoftDeletes;

    /** Workflow métier ascendant — étapes institutionnelles */
    public const STATUS_BROUILLON = 'brouillon';

    public const STATUS_EN_COURS = 'en_cours';

    public const STATUS_CLOTUREE = 'clôturée';

    public const STATUS_VALIDEE_IS = 'validée_IS';

    public const STATUS_VALIDEE_COPRI = 'validée_COPRI';

    protected $attributes = [
        'mission_status' => self::STATUS_BROUILLON,
    ];

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_BROUILLON => 'Brouillon',
            self::STATUS_EN_COURS => 'En cours',
            self::STATUS_CLOTUREE => 'Clôturée',
            self::STATUS_VALIDEE_IS => 'Validée IS',
            self::STATUS_VALIDEE_COPRI => 'Validée COPRI',
        ];
    }

    protected $fillable = [
        'organisation',
        'reference',
        'objet',
        'description',
        'periode_audit',
        'ordre_mission_reference',
        'date_ordre_mission',
        'observations_generales',
        'date_debut',
        'date_fin',
        'deadline',
        'auditeur_id',
        'department_id',
        'mission_type',
        'mission_status',
        'workflow_instance_id',
        'priority',
        'sensitivity_level',
        'confidentiality_level',
        'supervising_department_id',
        'treasury_entity_id',
        'treasury_service_id',
        'audit_domain_id',
        'audit_template_id',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'deadline' => 'date',
            'date_ordre_mission' => 'date',
        ];
    }

    /*
    |--------------------------------------------------
    | RELATIONS
    |--------------------------------------------------
    */

    public function processus()
    {
        return $this->hasMany(Processus::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function auditConsolidations()
    {
        return $this->hasMany(DepartmentAuditConsolidation::class);
    }

    public function missionDocuments()
    {
        return $this->hasMany(MissionDocument::class);
    }

    public function swotPreviews()
    {
        return $this->hasMany(MissionSwotPreview::class);
    }

    public function raciPreviews()
    {
        return $this->hasMany(MissionRaciPreview::class);
    }

    public function swotAnalyses()
    {
        return $this->hasMany(SwotAnalysis::class)->orderByDesc('id');
    }

    public function swotRecommendations()
    {
        return $this->hasMany(SwotRecommendation::class)->orderByDesc('id');
    }

    public function swotSnapshots()
    {
        return $this->hasMany(SwotSnapshot::class)->orderByDesc('captured_at');
    }

    public function raciMatrices()
    {
        return $this->hasMany(RaciMatrix::class)->orderByDesc('id');
    }

    public function raciAssignments()
    {
        return $this->hasMany(RaciAssignment::class)->orderByDesc('id');
    }

    public function raciSnapshots()
    {
        return $this->hasMany(RaciSnapshot::class)->orderByDesc('captured_at');
    }

    public function raciValidations()
    {
        return $this->hasMany(RaciValidation::class)->orderByDesc('id');
    }

    public function riskProjection()
    {
        return $this->hasOne(MissionRiskProjection::class);
    }

    public function auditeur()
    {
        return $this->belongsTo(User::class, 'auditeur_id')->withTrashed();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisingDepartment()
    {
        return $this->belongsTo(Department::class, 'supervising_department_id');
    }

    public function treasuryEntity()
    {
        return $this->belongsTo(\App\Models\Dgcpt\TreasuryEntity::class);
    }

    public function treasuryService()
    {
        return $this->belongsTo(\App\Models\Dgcpt\TreasuryService::class);
    }

    public function auditDomain()
    {
        return $this->belongsTo(\App\Models\Dgcpt\AuditDomain::class);
    }

    public function auditTemplate()
    {
        return $this->belongsTo(\App\Models\Dgcpt\AuditTemplate::class);
    }

    public function auditPlans()
    {
        return $this->hasMany(AuditPlan::class);
    }

    public function workflowEvents()
    {
        return $this->hasMany(MissionWorkflowEvent::class)->orderByDesc('created_at');
    }

    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function missionTeamMembers()
    {
        return $this->hasMany(MissionTeamMember::class)->orderByDesc('is_lead')->orderBy('mission_role');
    }

    public function auditGroups()
    {
        return $this->hasMany(MissionAuditGroup::class)->orderBy('name');
    }

    /**
     * Utilisateurs pouvant être affectés à l’équipe de mission (IAM ≠ rôle missionnel).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function eligibleTeamUsers(User $actor): \Illuminate\Database\Eloquent\Collection
    {
        $deptId = $this->department_id !== null ? (int) $this->department_id : null;
        if ($deptId === null) {
            return User::query()->whereRaw('1 = 0')->get();
        }

        $departmentIds = Department::subtreeIds($deptId);

        return User::query()
            ->where('approval_status', User::APPROVAL_STATUS_APPROVED)
            ->where('active', true)
            ->whereIn('department_id', $departmentIds)
            ->orderBy('name')
            ->orderBy('email')
            ->get();
    }

    /**
     * Isolation des données : missions du pôle, supervision ou visibilité nationale.
     *
     * @param  Builder<Mission>  $query
     * @return Builder<Mission>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        $user->loadMissing('institutionalRole');

        if ($user->institutionalRole?->slug === 'copri') {
            return $query->where('mission_status', self::STATUS_VALIDEE_IS);
        }

        if ($user->canViewAllInstitutionalData()) {
            return $query;
        }

        $deptId = $user->department_id;
        if ($deptId === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canSuperviseEntirePole()) {
            return $query->where('department_id', $deptId);
        }

        return $query->where(function (Builder $q) use ($deptId) {
            $q->where('department_id', $deptId)
                ->orWhere('supervising_department_id', $deptId);
        });
    }

    /*
    |--------------------------------------------------
    | MOTEUR PLAN D’AUDIT AUTOMATIQUE
    |--------------------------------------------------
    */

    public function genererPlanAuditAutomatique()
    {
        // Éviter duplication de plan
        if ($this->auditPlans()->where('niveau', 'critique')->exists()) {
            return;
        }

        // Récupérer risques critiques de la mission
        $risquesCritiques = Risque::where('criticite_residuel', CriticalityLevel::Critical->value)
            ->whereHas('actif.processus', function ($q) {
                $q->where('mission_id', $this->id);
            })
            ->get();

        if ($risquesCritiques->count() == 0) {
            return;
        }

        // Création plan audit
        $plan = AuditPlan::create([
            'mission_id' => $this->id,
            'titre' => 'Plan d’audit automatique',
            'description' => 'Généré automatiquement suite aux risques critiques détectés.',
            'niveau' => 'critique',
        ]);

        // Création programme d’audit
        foreach ($risquesCritiques as $risque) {

            AuditProgramme::create([
                'audit_plan_id' => $plan->id,
                'procedure' => 'Tester le contrôle lié au risque : '.$risque->description,
                'type' => 'test',
            ]);

            AuditProgramme::create([
                'audit_plan_id' => $plan->id,
                'procedure' => 'Réaliser entretien avec le responsable du risque.',
                'type' => 'entretien',
            ]);
        }
    }
}
