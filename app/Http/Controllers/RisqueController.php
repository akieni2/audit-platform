<?php

namespace App\Http\Controllers;

use App\Models\Risque;
use App\Models\Actif;
use Illuminate\Http\Request;

class RisqueController extends Controller
{

    public function index($id)
    {

        $actif = Actif::findOrFail($id);

        $risques = Risque::where('actif_id',$id)->get();

        return view('risques.index', compact('actif','risques'));

    }

    public function store(Request $request)
   {

    $risque = Risque::create([

        'actif_id'=>$request->actif_id,
        'description'=>$request->description,
        'impact_inherent'=>$request->impact_inherent,
        'probabilite_inherent'=>$request->probabilite_inherent,
        'score_inherent'=>$request->impact_inherent * $request->probabilite_inherent

    ]);

    $risque->calculerRisqueResiduel();

    return back();

   }

}
