<?php

namespace Tests\Feature;

use App\Models\Actif;
use App\Models\Controle;
use App\Models\Processus;
use App\Models\Risque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class CartographieExcelExportTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_workbook_export_returns_xlsx_with_expected_sheets(): void
    {
        $department = $this->createDepartment('XLS');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);

        $processus = Processus::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Processus IT',
        ]);

        $actif = Actif::query()->create([
            'processus_id' => $processus->id,
            'nom' => 'GESTION DES SAUVEGARDES',
            'type' => 'essentiel',
            'description' => 'Préserver l\'intégrité des données',
        ]);

        $risque = Risque::query()->create([
            'actif_id' => $actif->id,
            'description' => 'GESTION DES SAUVEGARDES',
            'impact_inherent' => 5,
            'probabilite_inherent' => 4,
            'statut_risque' => 'identifie',
            'lifecycle_status' => 'promoted',
            'proprietaire' => 'DSI',
        ]);

        Controle::query()->create([
            'risque_id' => $risque->id,
            'description' => 'Sauvegarde journalière',
            'type' => 'preventif',
            'efficacite' => 'moyenne',
        ]);

        $risque->calculerRisqueResiduel();

        $response = $this->actingAs($user)
            ->get(route('cartographie.export.workbook', $mission));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'carto_xlsx_');
        file_put_contents($temp, $response->getContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($temp));
        $sheetNames = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                $sheetNames[] = $name;
            }
        }
        $zip->close();
        unlink($temp);

        $this->assertGreaterThanOrEqual(6, count($sheetNames));
    }

    public function test_actifs_export_is_available_for_authorized_user(): void
    {
        $department = $this->createDepartment('XLA');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);

        $this->actingAs($user)
            ->get(route('cartographie.export.actifs', $mission))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_export_requires_authentication(): void
    {
        $department = $this->createDepartment('XLG');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);

        $this->get(route('cartographie.export.workbook', $mission))
            ->assertRedirect();
    }
}
