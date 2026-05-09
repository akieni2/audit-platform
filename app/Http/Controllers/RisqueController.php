<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRisqueRequest;
use App\Http\Requests\UpdateRisqueRequest;
use App\Models\Actif;
use App\Models\Risque;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RisqueController extends Controller
{
    public function index(int $id): View
    {
        $actif = Actif::findOrFail($id);

        $risques = Risque::where('actif_id', $id)
            ->with('controles')
            ->orderByDesc('score_inherent')
            ->get();

        return view('risques.index', compact('actif', 'risques'));
    }

    public function store(StoreRisqueRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['statut_risque'] = $data['statut_risque'] ?? 'identifie';

        $risque = Risque::create($data);
        $risque->calculerRisqueResiduel();

        return back()->with('status', 'Risque enregistré.');
    }

    public function update(UpdateRisqueRequest $request, Risque $risque): RedirectResponse
    {
        $risque->update($request->validated());
        $risque->calculerRisqueResiduel();

        return back()->with('status', 'Risque mis à jour.');
    }
}
