<?php

namespace App\Http\Controllers;

use App\Http\Requests\Missions\StoreMissionTeamMemberRequest;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Services\Iam\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MissionTeamMemberController extends Controller
{
    public function store(StoreMissionTeamMemberRequest $request, Mission $mission): RedirectResponse
    {
        $role = (string) $request->validated('mission_role');

        DB::transaction(function () use ($request, $mission, $role): void {
            if ($role === MissionTeamMember::ROLE_CHEF_MISSION) {
                MissionTeamMember::demoteOtherChefs($mission);
            }

            $member = MissionTeamMember::query()->create([
                'mission_id' => $mission->id,
                'user_id' => (int) $request->validated('user_id'),
                'mission_role' => $role,
                'designation' => $request->validated('designation'),
                'is_lead' => $role === MissionTeamMember::ROLE_CHEF_MISSION,
                'assigned_at' => now(),
                'assigned_by' => $request->user()?->id,
            ]);

            if ($role === MissionTeamMember::ROLE_CHEF_MISSION) {
                $mission->update(['auditeur_id' => $member->user_id]);
            }

            app(SecurityAuditService::class)->missionTeamMemberAssigned(
                $request->user(),
                $mission->fresh(),
                $member->fresh(['user']),
                $request
            );
        });

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'Membre d’équipe ajouté.');
    }

    public function destroy(Request $request, Mission $mission, MissionTeamMember $teamMember): RedirectResponse
    {
        $this->authorize('update', $mission);

        abort_unless((int) $teamMember->mission_id === (int) $mission->id, 404);

        $fresh = $teamMember->fresh(['user']);

        DB::transaction(function () use ($mission, $teamMember): void {
            if ($teamMember->mission_role === MissionTeamMember::ROLE_CHEF_MISSION
                && (int) $mission->auditeur_id === (int) $teamMember->user_id) {
                $mission->update(['auditeur_id' => null]);
            }
            $teamMember->delete();
        });

        if ($fresh !== null) {
            app(SecurityAuditService::class)->missionTeamMemberRemoved(
                $request->user(),
                $mission,
                $fresh,
                $request
            );
        }

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'Membre retiré de l’équipe de mission.');
    }
}
