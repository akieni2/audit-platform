<?php

namespace App\Http\Controllers;

use App\Models\Actif;
use App\Models\Processus;
use Illuminate\Http\Request;

class ActifController extends Controller
{

    public function index($id)
    {
        $processus = Processus::findOrFail($id);

        $actifs = Actif::where('processus_id',$id)->get();

        return view('actifs.index', compact('processus','actifs'));
    }

    public function store(Request $request)
    {

        Actif::create([

            'processus_id' => $request->processus_id,
            'nom' => $request->nom,
            'type' => $request->type,
            'description' => $request->description

        ]);

        return back();

    }

}
