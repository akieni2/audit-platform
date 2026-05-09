<?php

namespace App\Http\Controllers;

use App\Models\ActionCorrective;
use App\Models\Department;
use App\Models\Mission;
use App\Models\Risque;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $user->loadMissing('institutionalRole');

        $focusDepartmentId = $this->resolveDashboardDepartmentFocus($request, $user);
        $focusedDepartment = $focusDepartmentId !== null
            ? Department::query()->whereKey($focusDepartmentId)->first()
            : null;

        $missionsVisible = $this->missionsForDashboard($user, $focusDepartmentId);
        $missions = (clone $missionsVisible)->count();

        $risquesVisible = $this->risquesForDashboard($user, $focusDepartmentId);
        $risques = (clone $risquesVisible)->count();

        $risquesCritiques = (clone $risquesVisible)->where('score_residuel', '>=', 16)->count();

        $actionsOuvertes = ActionCorrective::query()
            ->where('statut', 'ouvert')
            ->whereHas('risque', fn ($q) => $this->applyRisqueDashboardScope($q, $user, $focusDepartmentId))
            ->count();

        $actionsRetard = ActionCorrective::query()
            ->where('statut', '!=', 'ferme')
            ->whereHas('risque', fn ($q) => $this->applyRisqueDashboardScope($q, $user, $focusDepartmentId))
            ->get()
            ->filter(fn ($a) => $a->isOverdue())
            ->count();

        $services = Service::query()
            ->whereHas('mission', fn ($q) => $this->applyMissionDashboardScope($q, $user, $focusDepartmentId))
            ->get();

        foreach ($services as $service) {
            $service->risques_count = (clone $risquesVisible)
                ->whereHas('actif.processus', function ($q) use ($service) {
                    $q->where('mission_id', $service->mission_id);
                })
                ->count();
        }

        $departments = Department::query()
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return view('dashboard', [
            'missions' => $missions,
            'risques' => $risques,
            'risquesCritiques' => $risquesCritiques,
            'actionsOuvertes' => $actionsOuvertes,
            'actionsRetard' => $actionsRetard,
            'services' => $services,
            'departments' => $departments,
            'dashboardDepartmentFocusId' => $focusDepartmentId,
            'focusedDepartment' => $focusedDepartment,
        ]);
    }

    /**
     * Pour les profils à visibilité nationale : focus optionnel sur un pôle (session + query).
     */
    private function resolveDashboardDepartmentFocus(Request $request, User $user): ?int
    {
        if (! $user->canViewAllInstitutionalData()) {
            $request->session()->forget('dashboard_department_focus');

            return null;
        }

        if ($request->query('department') === 'all') {
            $request->session()->forget('dashboard_department_focus');

            return null;
        }

        if ($request->filled('department')) {
            $id = (int) $request->query('department');
            if ($id > 0 && Department::query()->whereKey($id)->exists()) {
                $request->session()->put('dashboard_department_focus', $id);

                return $id;
            }

            return $request->session()->get('dashboard_department_focus');
        }

        $sessionId = $request->session()->get('dashboard_department_focus');
        if ($sessionId !== null && Department::query()->whereKey($sessionId)->exists()) {
            return (int) $sessionId;
        }

        $request->session()->forget('dashboard_department_focus');

        return null;
    }

    /**
     * @param  Builder<Mission>  $query
     * @return Builder<Mission>
     */
    private function applyMissionDashboardScope(Builder $query, User $user, ?int $focusDepartmentId): Builder
    {
        if ($focusDepartmentId !== null && $user->canViewAllInstitutionalData()) {
            return $query->where(function (Builder $q) use ($focusDepartmentId) {
                $q->where('department_id', $focusDepartmentId)
                    ->orWhere('supervising_department_id', $focusDepartmentId);
            });
        }

        return $query->visibleToUser($user);
    }

    /**
     * @param  Builder<Risque>  $query
     * @return Builder<Risque>
     */
    private function applyRisqueDashboardScope(Builder $query, User $user, ?int $focusDepartmentId): Builder
    {
        if ($focusDepartmentId !== null && $user->canViewAllInstitutionalData()) {
            return $query->where(function (Builder $outer) use ($focusDepartmentId) {
                $outer->where('owner_department_id', $focusDepartmentId)
                    ->orWhere('source_department_id', $focusDepartmentId)
                    ->orWhere('target_department_id', $focusDepartmentId)
                    ->orWhere(function (Builder $q) use ($focusDepartmentId) {
                        $q->where('shared', true)
                            ->where(function (Builder $inner) use ($focusDepartmentId) {
                                $inner->whereNull('target_department_id')
                                    ->orWhere('target_department_id', $focusDepartmentId);
                            });
                    })
                    ->orWhereHas(
                        'actif.processus.mission',
                        fn (Builder $mq) => $mq->where(function (Builder $m) use ($focusDepartmentId) {
                            $m->where('department_id', $focusDepartmentId)
                                ->orWhere('supervising_department_id', $focusDepartmentId);
                        })
                    );
            });
        }

        return $query->visibleToUser($user);
    }

    /**
     * @return Builder<Mission>
     */
    private function missionsForDashboard(User $user, ?int $focusDepartmentId): Builder
    {
        $q = Mission::query();

        return $this->applyMissionDashboardScope($q, $user, $focusDepartmentId);
    }

    /**
     * @return Builder<Risque>
     */
    private function risquesForDashboard(User $user, ?int $focusDepartmentId): Builder
    {
        $q = Risque::query();

        return $this->applyRisqueDashboardScope($q, $user, $focusDepartmentId);
    }
}
