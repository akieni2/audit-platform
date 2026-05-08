<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Actif;
use App\Models\Controle;
use App\Models\ActionCorrective;
use App\Models\RecommendationLibrary;

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
        'niveau'
    ];

    /*
    |--------------------------------------------------
    | RELATIONS
    |--------------------------------------------------
    */

    public function actif()
    {
        return $this->belongsTo(Actif::class);
    }

    public function controles()
    {
        return $this->hasMany(Controle::class);
    }

    public function actionsCorrectives()
    {
        return $this->hasMany(ActionCorrective::class);
    }

    /*
    |--------------------------------------------------
    | CALCUL RISQUE RESIDUEL
    |--------------------------------------------------
    */

    public function calculerRisqueResiduel()
    {
        $scoreInherent = $this->score_inherent;

        $controle = $this->controles()->first();

        if(!$controle){
            return;
        }

        switch($controle->efficacite){

            case 'faible':
                $coef = 0.8;
                break;

            case 'moyenne':
                $coef = 0.5;
                break;

            case 'forte':
                $coef = 0.2;
                break;

            default:
                $coef = 1;
        }

        $scoreResiduel = round($scoreInherent * $coef);

        $this->score_residuel = $scoreResiduel;

        $this->impact_residuel = $this->impact_inherent;

        if($this->impact_inherent > 0){
            $this->probabilite_residuel = ceil($scoreResiduel / $this->impact_inherent);
        }

        $this->save();

        // Déclenche moteur plan d’action automatique
        $this->genererPlanActionAutomatique();

        // Déclenche moteur plan d’audit automatique
        if($this->score_residuel >= 16 && $this->actif){

            $mission = optional($this->actif->processus)->mission;
            if($mission){
                $mission->genererPlanAuditAutomatique();
            }
        }
    }

    /*
    |--------------------------------------------------
    | MOTEUR AUTOMATIQUE PLAN D'ACTION
    |--------------------------------------------------
    */

    public function genererPlanActionAutomatique()
    {
        // On ne déclenche que pour risques >= 10
        if($this->score_residuel < 10){
            return;
        }

        // Éviter doublon d'actions
        if($this->actionsCorrectives()->count() > 0){
            return;
        }

        // Recherche recommandations adaptées
        $recommendations = RecommendationLibrary::all();

        foreach($recommendations as $rec){

            ActionCorrective::create([
                'risque_id' => $this->id,
                'description' => $rec->description,
                'responsable' => 'À définir',
                'date_echeance' => now()->addDays(
                    $this->score_residuel >= 16 ? 7 : 30
                ),
                'statut' => 'ouvert'
            ]);
        }
    }
}
