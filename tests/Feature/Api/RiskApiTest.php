<?php

namespace Tests\Feature\Api;

use App\Models\Actif;
use App\Models\Mission;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiskApiTest extends TestCase
{
    use RefreshDatabase;

    private function missionWithRisk(User $owner): array
    {
        $mission = Mission::create([
            'organisation' => 'API Org',
            'description' => null,
            'date_debut' => now()->toDateString(),
            'date_fin' => null,
            'auditeur_id' => $owner->id,
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

        $risque = Risque::create([
            'actif_id' => $actif->id,
            'description' => 'API risque',
            'impact_inherent' => 3,
            'probabilite_inherent' => 3,
            'statut_risque' => 'identifie',
        ]);

        return [$mission, $risque];
    }

    public function test_guest_cannot_access_api(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->missionWithRisk($user);

        $this->getJson("/api/v1/missions/{$mission->id}/risques")
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_risques_for_mission(): void
    {
        $user = User::factory()->create();
        [$mission, $risque] = $this->missionWithRisk($user);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/missions/{$mission->id}/risques");

        $response->assertOk()
            ->assertJsonFragment(['id' => $risque->id]);
    }

    public function test_cartography_endpoint_returns_heatmap_and_dashboard(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->missionWithRisk($user);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/missions/{$mission->id}/risk-cartography");

        $response->assertOk()
            ->assertJsonStructure([
                'mission_id',
                'heatmap',
                'heatmap_residual',
                'dashboard' => [
                    'critical_count',
                    'top_risques',
                    'monthly_creation',
                    'by_department',
                ],
            ]);
    }
}
