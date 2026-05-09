<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class MissionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->orderByDesc('id')
            ->get();

        return view('missions.index', compact('missions'));
    }

    public function create()
    {
        return view('missions.create');
    }

    public function store(Request $request)
    {
        Mission::create([
            'organisation' => $request->organisation,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'auditeur_id' => Auth::id()
        ]);

        return redirect()->route('missions.index');
    }
public function edit($id)
{
    $mission = Mission::findOrFail($id);

    return view('missions.edit', compact('mission'));
}

public function update(Request $request, $id)
{
    $mission = Mission::findOrFail($id);

    $mission->update([
        'organisation' => $request->organisation,
        'description' => $request->description,
        'date_debut' => $request->date_debut,
        'date_fin' => $request->date_fin,
    ]);

    return redirect()->route('missions.index');
}

}
