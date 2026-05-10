<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\Controle;
use App\Models\Risque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControleController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $risque = $this->visibleRisque($id)->load('controles');

        return view('controles.index', [
            'risque' => $risque,
            'controles' => $risque->controles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'risque_id' => ['required', 'integer', 'exists:risques,id'],
            'description' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:preventif,detectif,correctif'],
            'efficacite' => ['required', 'in:faible,moyenne,forte'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
        ]);

        $risque = $this->visibleRisque((int) $validated['risque_id']);

        Controle::create([
            'risque_id' => $risque->id,
            'description' => strip_tags($validated['description']),
            'type' => $validated['type'],
            'efficacite' => $validated['efficacite'],
            'commentaire' => isset($validated['commentaire'])
                ? strip_tags($validated['commentaire'])
                : null,
        ]);

        $risque->refresh()->calculerRisqueResiduel();

        return back()->with('status', 'Contrôle enregistré et risque résiduel recalculé.');
    }
}
