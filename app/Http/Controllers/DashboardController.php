<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Risque;
use App\Models\ActionCorrective;
use App\Models\Service;

class DashboardController extends Controller
{
    public function index()
    {

        /*
        |-----------------------------------------
        | KPI PRINCIPAUX
        |-----------------------------------------
        */

        $missions = Mission::count();

        $risques = Risque::count();

        $risquesCritiques = Risque::where('score_residuel','>=',16)->count();

        $actionsOuvertes = ActionCorrective::where('statut','ouvert')->count();

        $actionsRetard = ActionCorrective::all()
            ->filter(fn($a)=>$a->isOverdue())
            ->count();


        /*
        |-----------------------------------------
        | SERVICES POUR GRAPHIQUE
        |-----------------------------------------
        */

        $services = Service::all();

        foreach($services as $service){

        $service->risques_count = Risque::whereHas('actif.processus', function($q) use ($service){
        $q->where('mission_id',$service->mission_id);
        })->count();

}


        /*
        |-----------------------------------------
        | RETOUR VUE
        |-----------------------------------------
        */

        return view('dashboard', compact(
            'missions',
            'risques',
            'risquesCritiques',
            'actionsOuvertes',
            'actionsRetard',
            'services'
        ));
    }
}
