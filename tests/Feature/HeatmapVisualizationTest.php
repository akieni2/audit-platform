<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class HeatmapVisualizationTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_heatmap_visualization_page_renders_matrix_clusters_and_drilldown(): void
    {
        $department = $this->createDepartment('HTM');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);

        $this->actingAs($user)
            ->get(route('cartographie.index', $mission))
            ->assertOk()
            ->assertSee('Cartographie visuelle enterprise')
            ->assertSee('Matrice 5x5 interactive')
            ->assertSee('Clustering')
            ->assertSee('Drilldown risques');
    }
}
