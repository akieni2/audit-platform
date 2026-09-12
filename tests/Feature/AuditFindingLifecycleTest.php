<?php

namespace Tests\Feature;

use App\Models\Constat;
use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Audit\ConstatWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFindingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finding_moves_from_draft_to_final_then_creates_risk_recommendation_and_action(): void
    {
        $department = Department::query()->create(['name' => 'Pôle informatique', 'code' => 'PI', 'type' => 'pole', 'active' => true]);
        $user = User::factory()->create(['department_id' => $department->id, 'approval_status' => 'approved', 'active' => true]);
        $department->update(['supervisor_user_id' => $user->id]);
        $mission = Mission::query()->create(['organisation' => 'Audit DSI', 'date_debut' => today(), 'auditeur_id' => $user->id, 'department_id' => $department->id]);
        $finding = Constat::query()->create([
            'reference' => 'CST-TEST-0001', 'mission_id' => $mission->id,
            'criterion' => 'COBIT 2019 APO12', 'condition_observed' => 'Absence de registre formel des risques SI.',
            'description' => 'Absence de registre formel des risques SI.', 'cause' => 'Processus non institué.',
            'consequence' => 'Les risques ne sont pas arbitrés.', 'gravite' => 'critical',
            'recommandation' => 'Instituer un registre des risques SI.', 'created_by' => $user->id,
        ]);

        $workflow = app(ConstatWorkflowService::class);
        $workflow->transition($finding, $user, 'submit_review');
        $workflow->recordTeamReview($finding->fresh(), $user, 'approved', 'Avis favorable');
        $workflow->transition($finding->fresh(), $user, 'validate_mission');
        $workflow->transition($finding->fresh(), $user, 'open_contradictory');
        $finding->auditeeResponses()->create([
            'responded_by' => $user->id, 'department_id' => $department->id,
            'position' => 'accepted', 'observation' => 'Constat accepté.',
            'proposed_action' => 'Créer le registre.', 'responded_at' => now(),
        ]);
        $workflow->transition($finding->fresh(), $user, 'finalize');
        $risk = $workflow->convertToRisk($finding->fresh(), $user);
        $recommendation = $finding->recommendations()->firstOrFail();
        $recommendation->update(['status' => 'validated']);
        $action = $workflow->createAction($recommendation->fresh(), [
            'description' => 'Mettre en service le registre des risques.',
            'owner_user_id' => $user->id, 'owner_department_id' => $department->id,
            'date_echeance' => today()->addMonth()->toDateString(),
        ], $user);

        $this->assertSame(Constat::STATUS_FINAL, $finding->fresh()->status);
        $this->assertSame($finding->id, $risk->constat_id);
        $this->assertSame($risk->id, $recommendation->identified_risk_id);
        $this->assertSame($recommendation->id, $action->audit_recommendation_id);
        $this->assertDatabaseCount('constat_reviews', 5);
        $this->assertDatabaseCount('constat_auditee_responses', 1);
        $this->assertDatabaseCount('action_corrective_updates', 1);
    }

    public function test_risk_conversion_is_idempotent(): void
    {
        $department = Department::query()->create(['name' => 'Inspection', 'code' => 'IS', 'type' => 'inspection_services', 'active' => true]);
        $user = User::factory()->create(['department_id' => $department->id, 'approval_status' => 'approved', 'active' => true]);
        $mission = Mission::query()->create(['organisation' => 'Mission', 'date_debut' => today(), 'auditeur_id' => $user->id, 'department_id' => $department->id]);
        $finding = Constat::query()->create(['reference' => 'CST-TEST-0002', 'mission_id' => $mission->id, 'description' => 'Condition', 'condition_observed' => 'Condition', 'criterion' => 'Critère', 'gravite' => 'high', 'recommandation' => 'Recommandation', 'status' => Constat::STATUS_FINAL]);

        $first = app(ConstatWorkflowService::class)->convertToRisk($finding, $user);
        $second = app(ConstatWorkflowService::class)->convertToRisk($finding->fresh(), $user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('identified_risks', 1);
        $this->assertDatabaseCount('audit_recommendations', 1);
    }
}
