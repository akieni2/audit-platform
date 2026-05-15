<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class RaciBuilderTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_raci_builder_renders_templates_and_matrix(): void
    {
        $department = $this->governanceDepartment('RAB');
        $user = $this->governanceUser($department);
        $template = $this->governanceRaciTemplate($department);
        $role = $this->governanceRaciRole($template, $department);
        $template->assignments()->create([
            'raci_role_id' => $role->id,
            'process_label' => 'Preparation mission',
            'process_sort_order' => 0,
            'role_type' => 'accountable',
            'responsibility_level' => 'high',
            'status' => 'template',
        ]);

        $this->actingAs($user)
            ->get(route('raci-builder.index'))
            ->assertOk()
            ->assertSee('RACI Builder')
            ->assertSee($template->name);

        $this->actingAs($user)
            ->get(route('raci-builder.edit', $template))
            ->assertOk()
            ->assertSee('Matrice interactive')
            ->assertSee('Preparation mission');
    }
}
