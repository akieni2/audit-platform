<?php

use App\Services\Workflow\WorkflowCompatibilityService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_templates')
            || ! Schema::hasTable('workflow_stages')
            || ! Schema::hasTable('workflow_transitions')) {
            return;
        }

        $now = now();

        DB::table('workflow_templates')->updateOrInsert(
            ['code' => WorkflowCompatibilityService::DEFAULT_TEMPLATE_CODE],
            [
                'department_id' => null,
                'name' => 'Workflow DGCPT par défaut',
                'slug' => 'default-dgcpt-workflow',
                'description' => 'Workflow système compatible avec le parcours mission -> services -> entretiens -> risques -> cartographie -> actions -> rapports.',
                'active' => true,
                'is_system' => true,
                'version' => 1,
                'status' => 'published',
                'created_by' => null,
                'updated_by' => null,
                'published_at' => $now,
                'archived_at' => null,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $templateId = DB::table('workflow_templates')
            ->where('code', WorkflowCompatibilityService::DEFAULT_TEMPLATE_CODE)
            ->value('id');

        if ($templateId === null) {
            return;
        }

        $stages = [
            [
                'name' => 'Mission',
                'code' => 'mission',
                'description' => 'Cadre et contexte de mission.',
                'stage_type' => 'mission_context',
                'sort_order' => 10,
                'configuration' => json_encode(['module' => 'mission'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Services',
                'code' => 'services',
                'description' => 'Sélection et structuration des services audités.',
                'stage_type' => 'service_selection',
                'sort_order' => 20,
                'configuration' => json_encode(['module' => 'services'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Entretiens',
                'code' => 'entretiens',
                'description' => 'Conduite des entretiens et collecte runtime.',
                'stage_type' => 'entretien',
                'sort_order' => 30,
                'configuration' => json_encode(['module' => 'entretiens'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Risques',
                'code' => 'risques',
                'description' => 'Identification des risques issus du terrain.',
                'stage_type' => 'risk_identification',
                'sort_order' => 40,
                'configuration' => json_encode(['module' => 'risques'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Cartographie',
                'code' => 'cartographie',
                'description' => 'Projection heatmap et visualisation consolidée.',
                'stage_type' => 'heatmap',
                'sort_order' => 50,
                'configuration' => json_encode(['module' => 'cartographie'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
            [
                'name' => 'Actions',
                'code' => 'actions',
                'description' => 'Plan d’action et suivi correctif.',
                'stage_type' => 'action_plan',
                'sort_order' => 60,
                'configuration' => json_encode(['module' => 'actions'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => true,
                'role_scope' => null,
            ],
            [
                'name' => 'Rapports',
                'code' => 'rapports',
                'description' => 'Production et publication des rapports.',
                'stage_type' => 'reporting',
                'sort_order' => 70,
                'configuration' => json_encode(['module' => 'rapports'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_required' => true,
                'is_repeatable' => false,
                'role_scope' => null,
            ],
        ];

        foreach ($stages as $stage) {
            DB::table('workflow_stages')->updateOrInsert(
                [
                    'workflow_template_id' => $templateId,
                    'code' => $stage['code'],
                ],
                $stage + [
                    'workflow_template_id' => $templateId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $stageIds = DB::table('workflow_stages')
            ->where('workflow_template_id', $templateId)
            ->pluck('id', 'code');

        $transitions = [
            ['from' => 'mission', 'to' => 'services'],
            ['from' => 'services', 'to' => 'entretiens'],
            ['from' => 'entretiens', 'to' => 'risques'],
            ['from' => 'risques', 'to' => 'cartographie'],
            ['from' => 'cartographie', 'to' => 'actions'],
            ['from' => 'actions', 'to' => 'rapports'],
        ];

        foreach ($transitions as $transition) {
            $fromStageId = $stageIds[$transition['from']] ?? null;
            $toStageId = $stageIds[$transition['to']] ?? null;

            if ($fromStageId === null || $toStageId === null) {
                continue;
            }

            DB::table('workflow_transitions')->updateOrInsert(
                [
                    'workflow_template_id' => $templateId,
                    'from_stage_id' => $fromStageId,
                    'to_stage_id' => $toStageId,
                ],
                [
                    'condition_type' => null,
                    'condition_configuration' => null,
                    'role_required' => null,
                    'is_automatic' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_templates')) {
            return;
        }

        $templateId = DB::table('workflow_templates')
            ->where('code', WorkflowCompatibilityService::DEFAULT_TEMPLATE_CODE)
            ->value('id');

        if ($templateId === null) {
            return;
        }

        if (Schema::hasTable('workflow_transitions')) {
            DB::table('workflow_transitions')->where('workflow_template_id', $templateId)->delete();
        }

        if (Schema::hasTable('workflow_stages')) {
            DB::table('workflow_stages')->where('workflow_template_id', $templateId)->delete();
        }

        DB::table('workflow_templates')->where('id', $templateId)->delete();
    }
};
