<?php

namespace Tests\Feature;

use App\Models\ControlLibrary;
use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\QuestionnaireTemplate;
use App\Models\RaciTemplate;
use App\Models\SwotTemplate;
use App\Models\TenantContext;
use App\Models\WorkflowTemplate;
use App\Services\Governance\DepartmentAuditEnvironmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAuditEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_an_idempotent_audit_environment_from_the_selected_methodology(): void
    {
        $methodology = MethodologyTemplate::query()->create([
            'name' => 'Référentiel DSI',
            'slug' => 'referentiel-dsi',
            'framework_key' => 'DSI',
            'code' => 'DSI-AUDIT',
            'active' => true,
            'is_system' => false,
            'is_global' => true,
            'version' => 1,
            'lifecycle_status' => MethodologyTemplate::STATUS_PUBLISHED,
        ]);

        $department = Department::query()->create([
            'name' => 'Direction des Systèmes d’Information',
            'code' => 'DSI',
            'type' => 'direction',
            'active' => true,
            'default_methodology_template_id' => $methodology->id,
        ]);

        $service = app(DepartmentAuditEnvironmentService::class);
        $service->provision($department, $methodology);
        $service->provision($department->fresh(), $methodology);

        $this->assertSame(1, TenantContext::query()->where('department_id', $department->id)->count());
        $this->assertSame(1, WorkflowTemplate::query()->where('department_id', $department->id)->count());
        $this->assertSame(1, QuestionnaireTemplate::query()->whereJsonContains('department_scope', $department->id)->count());
        $this->assertSame(1, ControlLibrary::query()->where('department_id', $department->id)->count());
        $this->assertSame(1, RaciTemplate::query()->where('department_id', $department->id)->count());
        $this->assertSame(1, SwotTemplate::query()->where('department_id', $department->id)->count());
        $this->assertSame('ready', data_get($department->fresh()->intelligence_profile, 'audit_environment.status'));
    }
}
