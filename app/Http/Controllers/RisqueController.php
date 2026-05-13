<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Http\Requests\StoreRisqueRequest;
use App\Http\Requests\UpdateRisqueRequest;
use App\Models\Risque;
use App\Services\Risk\RiskRegistryPromotionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RisqueController extends Controller
{
    use ResolvesVisibleResources;

    public function __construct(
        private RiskRegistryPromotionService $registry,
    ) {}

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

        $risque = $this->registry->ingestLegacySubmission($data, $request->user());

        return back()->with('status', 'Risque enregistré dans le registre enterprise sous la référence '.$risque->risk_reference.'.');
    }

    public function update(UpdateRisqueRequest $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $risque->update($request->validated());
        $risque->calculerRisqueResiduel();

        return back()->with('status', 'Risque mis à jour.');
    }

    public function assignOwner(Request $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $validated = $request->validate([
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'owner_department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $risque = $this->registry->assignOwner(
            $risque,
            $validated['owner_user_id'] ?? null,
            $validated['owner_department_id'] ?? null,
        );

        return back()->with('status', 'Ownership mis à jour pour '.$risque->risk_reference.'.');
    }

    public function mitigate(Request $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $risque = $this->registry->mitigate(
            $risque,
            $request->user(),
            $request->string('comment')->toString(),
        );

        return back()->with('status', 'Risque '.$risque->risk_reference.' passé en mitigation.');
    }

    public function close(Request $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $risque = $this->registry->close(
            $risque,
            $request->user(),
            $request->string('comment')->toString(),
        );

        return back()->with('status', 'Risque '.$risque->risk_reference.' clôturé.');
    }

    public function archive(Request $request, Risque $risque): RedirectResponse
    {
        $this->authorize('update', $risque);

        $risque = $this->registry->archive(
            $risque,
            $request->user(),
            $request->string('comment')->toString(),
        );

        return back()->with('status', 'Risque '.$risque->risk_reference.' archivé.');
    }
}
