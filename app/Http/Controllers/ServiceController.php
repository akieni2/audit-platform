<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Mission $mission): View
    {
        $this->authorize('view', $mission);

        $services = Service::where('mission_id', $mission->id)->get();

        return view('services.index', compact('mission', 'services'));
    }

    public function store(Request $request)
    {
        $mission = Mission::query()
            ->whereKey((int) $request->mission_id)
            ->visibleToUser(auth()->user())
            ->firstOrFail();

        $this->authorize('view', $mission);

        Service::create([
            'mission_id' => $request->mission_id,
            'nom' => $request->nom,
            'responsable' => $request->responsable,
            'description' => $request->description,
        ]);

        return back();
    }
}
