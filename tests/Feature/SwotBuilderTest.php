<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class SwotBuilderTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_swot_builder_renders_templates_and_editor(): void
    {
        $department = $this->governanceDepartment('SWT');
        $user = $this->governanceUser($department);
        $template = $this->governanceSwotTemplate($department);
        $category = $this->governanceSwotCategory($template);
        $this->governanceSwotEntry($template, $category, $department);

        $this->actingAs($user)
            ->get(route('swot-builder.index'))
            ->assertOk()
            ->assertSee('SWOT Builder')
            ->assertSee($template->name);

        $this->actingAs($user)
            ->get(route('swot-builder.edit', $template))
            ->assertOk()
            ->assertSee('Matrice SWOT')
            ->assertSee('Forces');
    }
}
