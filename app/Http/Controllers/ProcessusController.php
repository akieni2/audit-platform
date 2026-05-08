<?php

namespace App\Http\Controllers;

use App\Models\Processus;
use App\Models\Mission;
use Illuminate\Http\Request;

class ProcessusController extends Controller
{

public function index($mission_id)
{
    $mission = Mission::findOrFail($mission_id);

    $processus = Processus::where('mission_id',$mission_id)->get();

    return view('processus.index',compact('mission','processus'));
}

public function store(Request $request)
{
    Processus::create([

        'mission_id'=>$request->mission_id,
        'nom'=>$request->nom,
        'description'=>$request->description

    ]);

    return back();
}

}
