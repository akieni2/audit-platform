<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    /** Workflow métier ascendant — étapes institutionnelles */
    public const STATUS_BROUILLON = 'brouillon';

    public const STATUS_EN_COURS = 'en_cours';

    public const STATUS_CLOTUREE = 'clôturée';

    public const STATUS_VALIDEE_IS = 'validée_IS';

    public const STATUS_VALIDEE_COPRI = 'validée_COPRI';

    protected $attributes = [
        'mission_status' => self::STATUS_BROUILLON,
    ];

    protected $fillable = [
        'organisation',
        'description',
        'date_debut',
        'date_fin',
        'auditeur_id',
        'department_id',
        'mission_type',
        'mission_status',
        'priority',
        'sensitivity_level',
        'confidentiality_level',
        'supervising_department_id',
    ];

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

    public function auditeur()
    {
        return $this->belongsTo(User::class,'auditeur_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisingDepartment()
    {
        return $this->belongsTo(Department::class, 'supervising_department_id');
    }

    public function auditPlans()
    {
        return $this->hasMany(AuditPlan::class);
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
            return $query->whereRaw('1 = 0');
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
        if($this->auditPlans()->where('niveau','critique')->exists()){
            return;
        }

        // Récupérer risques critiques de la mission
        $risquesCritiques = Risque::where('score_residuel','>=',16)
            ->whereHas('actif.processus', function($q){
                $q->where('mission_id',$this->id);
            })
            ->get();

        if($risquesCritiques->count() == 0){
            return;
        }

        // Création plan audit
        $plan = AuditPlan::create([
            'mission_id' => $this->id,
            'titre' => 'Plan d’audit automatique',
            'description' => 'Généré automatiquement suite aux risques critiques détectés.',
            'niveau' => 'critique'
        ]);

        // Création programme d’audit
        foreach($risquesCritiques as $risque){

            AuditProgramme::create([
                'audit_plan_id' => $plan->id,
                'procedure' => 'Tester le contrôle lié au risque : '.$risque->description,
                'type' => 'test'
            ]);

            AuditProgramme::create([
                'audit_plan_id' => $plan->id,
                'procedure' => 'Réaliser entretien avec le responsable du risque.',
                'type' => 'entretien'
            ]);
        }
    }
}
