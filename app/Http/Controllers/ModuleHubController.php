<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Questionnaire;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Pages d'accès aux modules nécessitant un contexte (mission / service / etc.).
 */
class ModuleHubController extends Controller
{
    public function entretiens(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->with('services')
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(fn (Mission $m) => $this->entry(
            $m,
            $m->services->first() !== null,
            $m->services->first() !== null
                ? route('entretiens.index', $m->services->first()->id)
                : null,
            'Créez d’abord un service audité pour cette mission (depuis la fiche mission).'
        ));

        return view('modules.hub', [
            'title' => 'Entretiens',
            'intro' => 'Accédez aux entretiens par service audité, ou créez un service depuis la liste des missions.',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    public function processus(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(fn (Mission $m) => $this->entry(
            $m,
            true,
            route('processus.index', $m->id),
            null
        ));

        return view('modules.hub', [
            'title' => 'Processus',
            'intro' => 'Sélectionnez une mission pour voir ou ajouter des processus.',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    public function actifs(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->with(['processus' => fn ($q) => $q->orderBy('id')])
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(function (Mission $m) {
            $processus = $m->processus->first();

            return $this->entry(
                $m,
                $processus !== null,
                $processus !== null
                    ? route('actifs.index', $processus->id)
                    : null,
                'Ajoutez d’abord un processus pour cette mission.'
            );
        });

        return view('modules.hub', [
            'title' => 'Actifs',
            'intro' => 'Les actifs sont rattachés à un processus. Choisissez une mission (premier processus affiché).',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    public function risques(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->with([
                'processus' => fn ($q) => $q->orderBy('id'),
                'processus.actifs' => fn ($q) => $q->orderBy('id'),
            ])
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(function (Mission $m) {
            $actif = $m->processus->first()?->actifs->first();

            return $this->entry(
                $m,
                $actif !== null,
                $actif !== null
                    ? route('risques.index', $actif->id)
                    : null,
                'Ajoutez un processus puis un actif pour accéder aux risques.'
            );
        });

        return view('modules.hub', [
            'title' => 'Risques',
            'intro' => 'Les risques sont rattachés à un actif. Le lien ouvre le premier actif de la mission s’il existe.',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    public function actionsCorrectives(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->with([
                'processus' => fn ($q) => $q->orderBy('id'),
                'processus.actifs' => fn ($q) => $q->orderBy('id'),
                'processus.actifs.risques' => fn ($q) => $q->orderByDesc('score_inherent'),
            ])
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(function (Mission $m) {
            $risque = $m->processus
                ->flatMap(fn ($p) => $p->actifs)
                ->flatMap(fn ($a) => $a->risques)
                ->first();

            return $this->entry(
                $m,
                $risque !== null,
                $risque !== null
                    ? route('actions.index', $risque->id)
                    : null,
                'Créez au moins un risque sur un actif de la mission pour gérer les actions correctives.'
            );
        });

        return view('modules.hub', [
            'title' => 'Actions correctives',
            'intro' => 'Les actions sont liées à un risque. Le lien ouvre le premier risque trouvé pour la mission.',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    public function rapports(): View
    {
        $user = Auth::user();
        $missions = Mission::query()
            ->when($user, fn ($q) => $q->visibleToUser($user))
            ->orderByDesc('date_debut')
            ->get();

        $entries = $missions->map(fn (Mission $m) => $this->entry(
            $m,
            true,
            route('missions.rapport', $m->id),
            null
        ));

        return view('modules.hub', [
            'title' => 'Rapports PDF',
            'intro' => 'Générez le rapport d’audit pour une mission.',
            'missionsIndexUrl' => route('missions.index'),
            'entries' => $entries,
        ]);
    }

    /** Page provisoire : liste des questionnaires (pas de CRUD dédié dans l’app). */
    public function questionnaires(): View
    {
        $items = Questionnaire::query()
            ->withCount('questions')
            ->orderBy('titre')
            ->get();

        return view('modules.questionnaires', [
            'items' => $items,
        ]);
    }

    /**
     * @return array{mission: Mission, ready: bool, url: ?string, hint: ?string}
     */
    private function entry(Mission $mission, bool $ready, ?string $url, ?string $hint): array
    {
        return [
            'mission' => $mission,
            'ready' => $ready && $url !== null,
            'url' => $url,
            'hint' => $hint,
        ];
    }
}
