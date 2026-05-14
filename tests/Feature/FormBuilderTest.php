<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_can_create_fields_publish_and_clone_new_draft(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $this->actingAs($user);

        $this->post(route('form-builder.store'), [
            'name' => 'Formulaire contrôle interne',
            'slug' => 'formulaire-controle-interne',
            'component_key' => 'dynamic_form',
            'department_scope' => [$department->id],
            'description' => 'Formulaire dynamique de contrôle',
        ])->assertRedirect();

        $template = FormTemplate::query()->where('slug', 'formulaire-controle-interne')->firstOrFail();
        $this->assertDatabaseHas('form_templates', [
            'id' => $template->id,
            'lifecycle_status' => FormTemplate::STATUS_DRAFT,
            'active' => false,
        ]);

        $this->post(route('form-builder.fields.store', $template), [
            'label' => 'Niveau de maîtrise',
            'field_key' => 'MAITRISE',
            'field_type' => FormField::TYPE_SELECT,
            'sort_order' => 0,
            'is_required' => '1',
            'options_text' => "Faible|low\nÉlevé|high",
            'validation_rules_json_text' => json_encode(['max:20']),
        ])->assertRedirect();

        $field = FormField::query()
            ->where('form_template_id', $template->id)
            ->where('field_key', 'MAITRISE')
            ->firstOrFail();

        $this->assertDatabaseHas('form_fields', [
            'id' => $field->id,
            'field_type' => FormField::TYPE_SELECT,
            'is_required' => true,
        ]);
        $this->assertDatabaseCount('form_field_options', 2);

        $this->post(route('form-builder.publish', $template))
            ->assertRedirect(route('form-builder.edit', $template));

        $template->refresh();
        $this->assertSame(FormTemplate::STATUS_PUBLISHED, $template->lifecycle_status);
        $this->assertTrue($template->active);
        $this->assertNotNull($template->signature_hash);

        $this->patch(route('form-builder.update', $template), [
            'name' => 'Formulaire contrôle interne v2',
            'slug' => 'formulaire-controle-interne-v2',
            'component_key' => 'dynamic_form',
            'department_scope' => [$department->id],
            'description' => 'Nouvelle version',
        ])->assertRedirect();

        $draft = FormTemplate::query()
            ->where('name', 'Formulaire contrôle interne v2')
            ->where('lifecycle_status', FormTemplate::STATUS_DRAFT)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame($template->id, $draft->id);
        $this->assertSame($template->id, $draft->source_template_id);
        $this->assertDatabaseHas('form_fields', [
            'form_template_id' => $draft->id,
            'field_key' => 'MAITRISE',
        ]);
        $this->assertDatabaseHas('form_field_options', [
            'form_field_id' => $draft->fields()->where('field_key', 'MAITRISE')->firstOrFail()->id,
            'value' => 'low',
        ]);
    }

    private function inspecteurNational(): User
    {
        $role = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'Inspecteur des Services',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        return User::factory()->create([
            'department_id' => null,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
    }

    private function department(): Department
    {
        return Department::query()->create([
            'name' => 'Pôle Formulaires',
            'code' => 'FORMS',
            'type' => 'pole',
            'description' => 'Form tests',
            'active' => true,
        ]);
    }
}
