<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesVisibleResources;
use App\Models\Actif;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActifController extends Controller
{
    use ResolvesVisibleResources;

    public function index(int $id): View
    {
        $processus = $this->visibleProcessus($id);

        $actifs = Actif::where('processus_id', $processus->id)->get();

        return view('actifs.index', compact('processus', 'actifs'));
    }

    public function store(Request $request)
    {
        $processus = $this->visibleProcessus((int) $request->processus_id);

        Actif::create([
            'processus_id' => $processus->id,
            'nom' => $request->nom,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        return back();
    }
}
