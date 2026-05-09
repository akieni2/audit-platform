<?php

namespace Tests\Feature\Risk;

use App\Models\Actif;
use App\Models\Mission;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RisqueCriticalPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function seedRiskChain(User $auditeur): Risque
    {
        $mission = Mission::create([
            'organisation' => 'Org Test',
            'description' => null,
            'date_debut' => now()->toDateString(),
            'date_fin' => null,
            'auditeur_id' => $auditeur->id,
        ]);

        $processus = Processus::create([
            'mission_id' => $mission->id,
            'nom' => 'Proc',
            'description' => null,
        ]);

        $actif = Actif::create([
            'processus_id' => $processus->id,
            'nom' => 'Actif',
            'type' => 'essentiel',
            'description' => null,
        ]);

        return Risque::create([
            'actif_id' => $actif->id,
            'description' => 'Risque critique test',
            'impact_inherent' => 5,
            'probabilite_inherent' => 5,
            'statut_risque' => 'identifie',
        ]);
    }

    public function test_auditeur_cannot_update_critical_risk(): void
    {
        $auditeur = User::factory()->create(['role' => 'auditeur']);
        $risque = $this->seedRiskChain($auditeur);

        $this->assertSame('critique', $risque->fresh()->criticite_inherent);

        $response = $this->actingAs($auditeur)->patch(route('risques.update', $risque), [
            'description' => 'Modifié',
            'impact_inherent' => 5,
            'probabilite_inherent' => 5,
            'statut_risque' => 'en_analyse',
        ]);

        $response->assertForbidden();
    }

    public function test_risk_manager_can_update_critical_risk(): void
    {
        $auditeur = User::factory()->create(['role' => 'auditeur']);
        $riskManager = User::factory()->create(['role' => 'risk_manager']);

        $risque = $this->seedRiskChain($auditeur);

        $response = $this->actingAs($riskManager)->patch(route('risques.update', $risque), [
            'description' => 'Modifié par RM',
            'impact_inherent' => 5,
            'probabilite_inherent' => 5,
            'statut_risque' => 'en_analyse',
        ]);

        $response->assertRedirect();
        $this->assertSame('Modifié par RM', $risque->fresh()->description);
    }

    public function test_auditeur_can_update_non_critical_risk(): void
    {
        $auditeur = User::factory()->create(['role' => 'auditeur']);

        $risque = $this->seedRiskChain($auditeur);
        $risque->update([
            'impact_inherent' => 2,
            'probabilite_inherent' => 2,
        ]);

        $this->assertSame('faible', $risque->fresh()->criticite_inherent);

        $response = $this->actingAs($auditeur)->patch(route('risques.update', $risque), [
            'description' => 'OK non critique',
            'impact_inherent' => 2,
            'probabilite_inherent' => 2,
            'statut_risque' => 'mitige',
        ]);

        $response->assertRedirect();
        $this->assertSame('OK non critique', $risque->fresh()->description);
    }
}
