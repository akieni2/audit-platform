<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $fillable = [
        'organisation',
        'description',
        'date_debut',
        'date_fin',
        'auditeur_id'
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

    public function auditPlans()
    {
        return $this->hasMany(AuditPlan::class);
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
