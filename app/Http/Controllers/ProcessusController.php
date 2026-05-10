<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\Mission;
use App\Models\Processus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessusController extends Controller
{
    use ResolvesVisibleResources;

    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $processus = Processus::where('mission_id', $mission->id)->get();

        return view('processus.index', compact('mission', 'processus'));
    }

    public function store(Request $request)
    {
        $mission = $this->visibleMission((int) $request->mission_id);
        $this->authorize('view', $mission);

        Processus::create([
            'mission_id' => $request->mission_id,
            'nom' => $request->nom,
            'description' => $request->description,
        ]);

        return back();
    }
}
