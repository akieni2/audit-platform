<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\Entretien;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntretienController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $service = $this->visibleService($id);

        $entretiens = Entretien::where('service_id', $service->id)->get();

        $questions = Question::query()->orderBy('id')->get();

        return view('entretiens.index', compact('service', 'entretiens', 'questions'));
    }

    public function store(Request $request)
    {
        $service = $this->visibleService((int) $request->service_id);

        Entretien::create([
            'mission_id' => $service->mission_id,
            'service_id' => $service->id,
            'responsable_nom' => $request->responsable_nom,
            'role' => $request->role,
            'chef_hierarchique' => $request->chef_hierarchique,
            'auditeur' => $request->auditeur,
            'date_entretien' => $request->date_entretien,
            'notes' => $request->notes,
        ]);

        return back();
    }
}
