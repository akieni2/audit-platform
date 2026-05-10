<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\ActionCorrective;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionCorrectiveController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $risque = $this->visibleRisque($id);

        $actions = ActionCorrective::where('risque_id', $risque->id)->get();

        return view('actions.index', compact('risque', 'actions'));
    }

    public function store(Request $request)
    {
        $risque = $this->visibleRisque((int) $request->risque_id);

        ActionCorrective::create([
            'risque_id' => $risque->id,
            'description' => $request->description,
            'responsable' => $request->responsable,
            'date_echeance' => $request->date_echeance,
            'statut' => 'ouvert',
        ]);

        return back();
    }
}
