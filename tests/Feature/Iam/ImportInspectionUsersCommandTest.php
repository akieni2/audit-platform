<?php

namespace Tests\Feature\Iam;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportInspectionUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $csvPath;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = Str::uuid()->toString();
        $this->csvPath = sys_get_temp_dir().DIRECTORY_SEPARATOR."agents-{$suffix}.csv";
        $this->credentialsPath = sys_get_temp_dir().DIRECTORY_SEPARATOR."credentials-{$suffix}.csv";

        file_put_contents($this->csvPath, implode(PHP_EOL, [
            'validation;intercom;nom_complet;fonction;role_systeme;code_structure;responsable;telephones;email',
            'Validé;;AGENT SANS BUREAU;Inspecteur Vérificateur;inspecteur_verificateur;DRA;Oui;077.00.00.00;agent.import@example.ga',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->csvPath);
        @unlink($this->credentialsPath);

        parent::tearDown();
    }

    public function test_simulation_does_not_create_users(): void
    {
        $this->catalog();

        $this->artisan('users:import-inspection', ['file' => $this->csvPath])
            ->expectsOutputToContain('Simulation réussie')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'agent.import@example.ga']);
    }

    public function test_confirmed_import_creates_user_without_intercom_and_assigns_supervisor(): void
    {
        [$department] = $this->catalog();

        $this->artisan('users:import-inspection', [
            'file' => $this->csvPath,
            '--execute' => true,
            '--confirm' => 'IMPORTER-AGENTS-INSPECTION',
            '--credentials' => $this->credentialsPath,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'agent.import@example.ga')->firstOrFail();
        $this->assertNull($user->intercom);
        $this->assertNull($user->matricule);
        $this->assertTrue($user->must_change_password);
        $this->assertSame($user->id, $department->fresh()->supervisor_user_id);
        $this->assertFileExists($this->credentialsPath);
    }

    /** @return array{Department,Role} */
    private function catalog(): array
    {
        $superRole = Role::query()->create([
            'slug' => 'super_admin',
            'name' => 'Super administrateur',
            'hierarchy_level' => 110,
            'active' => true,
        ]);
        $importRole = Role::query()->create([
            'slug' => 'inspecteur_verificateur',
            'name' => 'Inspecteur vérificateur',
            'hierarchy_level' => 60,
            'active' => true,
        ]);
        User::factory()->create(['role_id' => $superRole->id, 'active' => true]);
        $department = Department::query()->create([
            'name' => 'Division Réalisation des Audits',
            'code' => 'DRA',
            'type' => 'service',
            'active' => true,
        ]);

        return [$department, $importRole];
    }
}
