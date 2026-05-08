<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mission;
use App\Models\Risque;

class CartographieController extends Controller
{

    public function select()
    {
        $missions = Mission::all();

        return view('cartographie.select', compact('missions'));
    }


     public function index($id)
     {

    $mission = Mission::findOrFail($id);

    $risques = Risque::whereHas('actif.processus.mission', function($q) use ($id){
        $q->where('missions.id',$id);
    })->get();

    return view('cartographie.index', compact('risques','mission'));

 }

}
