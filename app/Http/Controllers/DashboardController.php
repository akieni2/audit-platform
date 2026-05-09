<?php

namespace App\Http\Controllers;

use App\Models\ActionCorrective;
use App\Models\Mission;
use App\Models\Risque;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $missionsVisible = Mission::query()->visibleToUser($user);
        $missions = (clone $missionsVisible)->count();

        $risquesVisible = Risque::query()->visibleToUser($user);
        $risques = (clone $risquesVisible)->count();

        $risquesCritiques = (clone $risquesVisible)->where('score_residuel', '>=', 16)->count();

        $actionsOuvertes = ActionCorrective::query()
            ->where('statut', 'ouvert')
            ->whereHas('risque', fn ($q) => $q->visibleToUser($user))
            ->count();

        $actionsRetard = ActionCorrective::query()
            ->where('statut', '!=', 'ferme')
            ->whereHas('risque', fn ($q) => $q->visibleToUser($user))
            ->get()
            ->filter(fn ($a) => $a->isOverdue())
            ->count();

        $services = Service::query()
            ->whereHas('mission', fn ($q) => $q->visibleToUser($user))
            ->get();

        foreach ($services as $service) {
            $service->risques_count = Risque::query()
                ->visibleToUser($user)
                ->whereHas('actif.processus', function ($q) use ($service) {
                    $q->where('mission_id', $service->mission_id);
                })
                ->count();
        }

        return view('dashboard', compact(
            'missions',
            'risques',
            'risquesCritiques',
            'actionsOuvertes',
            'actionsRetard',
            'services'
        ));
    }
}
