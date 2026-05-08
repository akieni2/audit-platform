<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActionCorrective;
use App\Models\Risque;

class ActionCorrectiveController extends Controller
{

    public function index($id)
    {

        $risque = Risque::findOrFail($id);

        $actions = ActionCorrective::where('risque_id',$id)->get();

        return view('actions.index', compact('risque','actions'));

    }

    public function store(Request $request)
    {

        ActionCorrective::create([

            'risque_id' => $request->risque_id,
            'description' => $request->description,
            'responsable' => $request->responsable,
            'date_echeance' => $request->date_echeance,
            'statut' => 'ouvert'

        ]);

        return back();

    }

}
