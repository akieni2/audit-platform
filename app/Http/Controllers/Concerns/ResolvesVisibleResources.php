<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Actif;
use App\Models\Mission;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait ResolvesVisibleResources
{
    protected function visibleMission(int $id, ?User $user = null): Mission
    {
        $user = $user ?? Auth::user();
        abort_unless($user, 403);

        return Mission::query()->whereKey($id)->visibleToUser($user)->firstOrFail();
    }

    protected function visibleActif(int $id, ?User $user = null): Actif
    {
        $user = $user ?? Auth::user();
        abort_unless($user, 403);

        return Actif::query()->whereKey($id)->visibleToUser($user)->firstOrFail();
    }

    protected function visibleRisque(int $id, ?User $user = null): Risque
    {
        $user = $user ?? Auth::user();
        abort_unless($user, 403);

        return Risque::query()->whereKey($id)->visibleToUser($user)->firstOrFail();
    }

    protected function visibleService(int $id, ?User $user = null): Service
    {
        $user = $user ?? Auth::user();
        abort_unless($user, 403);

        $service = Service::query()->whereKey($id)->firstOrFail();
        Mission::query()->whereKey($service->mission_id)->visibleToUser($user)->firstOrFail();

        return $service;
    }

    protected function visibleProcessus(int $id, ?User $user = null): Processus
    {
        $user = $user ?? Auth::user();
        abort_unless($user, 403);

        return Processus::query()->whereKey($id)->visibleToUser($user)->firstOrFail();
    }
}

