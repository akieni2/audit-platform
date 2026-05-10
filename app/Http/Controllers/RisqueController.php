<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\StoreRisqueRequest;
use App\Http\Requests\UpdateRisqueRequest;
use App\Models\Risque;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RisqueController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $actif = $this->visibleActif($id);

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

        $this->visibleActif((int) $data['actif_id']);

        $risque = Risque::create($data);
        $risque->calculerRisqueResiduel();

        return back()->with('status', 'Risque enregistré.');
    }

    public function update(UpdateRisqueRequest $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $risque->update($request->validated());
        $risque->calculerRisqueResiduel();

        return back()->with('status', 'Risque mis à jour.');
    }
}
