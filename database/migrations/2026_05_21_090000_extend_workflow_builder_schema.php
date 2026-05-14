<?php

use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Models\WorkflowStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workflow_templates')) {
            Schema::table('workflow_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('workflow_templates', 'signature_hash')) {
                    $table->string('signature_hash', 64)->nullable()->after('status');
                }

                if (! Schema::hasColumn('workflow_templates', 'deprecated_at')) {
                    $table->timestamp('deprecated_at')->nullable()->after('published_at');
                }

                if (! Schema::hasColumn('workflow_templates', 'source_template_id')) {
                    $table->foreignId('source_template_id')
                        ->nullable()
                        ->after('version')
                        ->constrained('workflow_templates')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('workflow_stages')) {
            Schema::table('workflow_stages', function (Blueprint $table) {
                if (! Schema::hasColumn('workflow_stages', 'ui_component')) {
                    $table->string('ui_component', 80)->nullable()->after('stage_type');
                }

                if (! Schema::hasColumn('workflow_stages', 'configuration_json')) {
                    $table->json('configuration_json')->nullable()->after('configuration');
                }

                if (! Schema::hasColumn('workflow_stages', 'validation_rules_json')) {
                    $table->json('validation_rules_json')->nullable()->after('configuration_json');
                }

                if (! Schema::hasColumn('workflow_stages', 'execution_mode')) {
                    $table->string('execution_mode', 32)->nullable()->after('validation_rules_json');
                }

                if (! Schema::hasColumn('workflow_stages', 'allow_skip')) {
                    $table->boolean('allow_skip')->default(false)->after('execution_mode');
                }

                if (! Schema::hasColumn('workflow_stages', 'requires_approval')) {
                    $table->boolean('requires_approval')->default(false)->after('allow_skip');
                }

                if (! Schema::hasColumn('workflow_stages', 'approval_role_id')) {
                    $table->foreignId('approval_role_id')
                        ->nullable()
                        ->after('requires_approval')
                        ->constrained('roles')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('workflow_stages', 'questionnaire_template_id')) {
                    $table->foreignId('questionnaire_template_id')
                        ->nullable()
                        ->after('approval_role_id')
                        ->constrained('questionnaire_templates')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('workflow_stages', 'form_schema_json')) {
                    $table->json('form_schema_json')->nullable()->after('questionnaire_template_id');
                }

                if (! Schema::hasColumn('workflow_stages', 'risk_matrix_schema_json')) {
                    $table->json('risk_matrix_schema_json')->nullable()->after('form_schema_json');
                }

                if (! Schema::hasColumn('workflow_stages', 'position_x')) {
                    $table->integer('position_x')->nullable()->after('risk_matrix_schema_json');
                }

                if (! Schema::hasColumn('workflow_stages', 'position_y')) {
                    $table->integer('position_y')->nullable()->after('position_x');
                }

                if (! Schema::hasColumn('workflow_stages', 'color')) {
                    $table->string('color', 32)->nullable()->after('position_y');
                }

                if (! Schema::hasColumn('workflow_stages', 'icon')) {
                    $table->string('icon', 80)->nullable()->after('color');
                }
            });
        }

        if (! Schema::hasTable('workflow_execution_logs')) {
            Schema::create('workflow_execution_logs', function (Blueprint $table) {
                $table->id();

                $table->foreignId('workflow_instance_id')
                    ->constrained('workflow_instances')
                    ->cascadeOnDelete();

                $table->foreignId('workflow_stage_execution_id')
                    ->nullable()
                    ->constrained('workflow_stage_executions')
                    ->nullOnDelete();

                $table->foreignId('workflow_stage_id')
                    ->nullable()
                    ->constrained('workflow_stages')
                    ->nullOnDelete();

                $table->string('event_name', 120);
                $table->string('status', 32)->nullable();
                $table->string('message', 280)->nullable();
                $table->json('payload')->nullable();

                $table->foreignId('actor_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('occurred_at')->useCurrent();

                $table->index(['workflow_instance_id', 'occurred_at']);
                $table->index(['workflow_stage_id', 'occurred_at']);
                $table->index(['event_name', 'occurred_at']);
            });
        }

        if (Schema::hasTable('workflow_stages')) {
            Schema::table('workflow_stages', function (Blueprint $table) {

                if (
                    Schema::hasColumn('workflow_stages', 'execution_mode')
                    && ! $this->indexExists('workflow_stages', 'workflow_stages_execution_mode_index')
                ) {
                    $table->index('execution_mode');
                }

                if (
                    Schema::hasColumn('workflow_stages', 'questionnaire_template_id')
                    && ! $this->indexExists('workflow_stages', 'workflow_stages_questionnaire_template_id_index')
                ) {
                    $table->index('questionnaire_template_id');
                }

                if (
                    Schema::hasColumn('workflow_stages', 'approval_role_id')
                    && ! $this->indexExists('workflow_stages', 'workflow_stages_approval_role_id_index')
                ) {
                    $table->index('approval_role_id');
                }

                if (
                    Schema::hasColumn('workflow_stages', 'position_x')
                    && Schema::hasColumn('workflow_stages', 'position_y')
                    && ! $this->indexExists('workflow_stages', 'workflow_stages_position_x_position_y_index')
                ) {
                    $table->index(['position_x', 'position_y']);
                }
            });
        }

        if (Schema::hasTable('workflow_stages')) {
            WorkflowStage::query()->each(function (WorkflowStage $stage): void {

                $stageType = WorkflowStageType::fromMixed(
                    (string) ($stage->getRawOriginal('stage_type') ?? $stage->stage_type)
                );

                $executionMode = WorkflowExecutionMode::fromMixed(
                    (string) ($stage->execution_mode ?? '')
                );

                if (! $executionMode instanceof WorkflowExecutionMode) {
                    $executionMode = match ($stageType) {
                        WorkflowStageType::Questionnaire => WorkflowExecutionMode::Questionnaire,
                        WorkflowStageType::Approval => WorkflowExecutionMode::Approval,
                        WorkflowStageType::Form => WorkflowExecutionMode::Form,
                        default => WorkflowExecutionMode::Automatic,
                    };
                }

                $stage->forceFill([
                    'stage_type' => $stageType?->value ?? WorkflowStageType::Custom->value,
                    'ui_component' => $stage->ui_component ?: 'stage-card',
                    'configuration_json' => $stage->configuration_json ?? $stage->configuration ?? [],
                    'execution_mode' => $executionMode->value,
                    'allow_skip' => $stage->allow_skip ?? false,
                    'requires_approval' => $stage->requires_approval ?? false,
                    'position_x' => $stage->position_x ?? ((int) $stage->sort_order * 240),
                    'position_y' => $stage->position_y ?? 0,
                    'color' => $stage->color ?: '#0A2A66',
                    'icon' => $stage->icon ?: 'workflow',
                ])->save();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_execution_logs')) {
            Schema::dropIfExists('workflow_execution_logs');
        }

        if (Schema::hasTable('workflow_stages')) {
            Schema::table('workflow_stages', function (Blueprint $table) {
                if ($this->indexExists('workflow_stages', 'workflow_stages_execution_mode_index')) {
                    $table->dropIndex(['execution_mode']);
                }
                if ($this->indexExists('workflow_stages', 'workflow_stages_questionnaire_template_id_index')) {
                    $table->dropIndex(['questionnaire_template_id']);
                }
                if ($this->indexExists('workflow_stages', 'workflow_stages_approval_role_id_index')) {
                    $table->dropIndex(['approval_role_id']);
                }
                if ($this->indexExists('workflow_stages', 'workflow_stages_position_x_position_y_index')) {
                    $table->dropIndex(['position_x', 'position_y']);
                }

                if (Schema::hasColumn('workflow_stages', 'approval_role_id')) {
                    $table->dropForeign(['approval_role_id']);
                }
                if (Schema::hasColumn('workflow_stages', 'questionnaire_template_id')) {
                    $table->dropForeign(['questionnaire_template_id']);
                }

                $dropColumns = array_values(array_filter([
                    Schema::hasColumn('workflow_stages', 'ui_component') ? 'ui_component' : null,
                    Schema::hasColumn('workflow_stages', 'configuration_json') ? 'configuration_json' : null,
                    Schema::hasColumn('workflow_stages', 'validation_rules_json') ? 'validation_rules_json' : null,
                    Schema::hasColumn('workflow_stages', 'execution_mode') ? 'execution_mode' : null,
                    Schema::hasColumn('workflow_stages', 'allow_skip') ? 'allow_skip' : null,
                    Schema::hasColumn('workflow_stages', 'requires_approval') ? 'requires_approval' : null,
                    Schema::hasColumn('workflow_stages', 'approval_role_id') ? 'approval_role_id' : null,
                    Schema::hasColumn('workflow_stages', 'questionnaire_template_id') ? 'questionnaire_template_id' : null,
                    Schema::hasColumn('workflow_stages', 'form_schema_json') ? 'form_schema_json' : null,
                    Schema::hasColumn('workflow_stages', 'risk_matrix_schema_json') ? 'risk_matrix_schema_json' : null,
                    Schema::hasColumn('workflow_stages', 'position_x') ? 'position_x' : null,
                    Schema::hasColumn('workflow_stages', 'position_y') ? 'position_y' : null,
                    Schema::hasColumn('workflow_stages', 'color') ? 'color' : null,
                    Schema::hasColumn('workflow_stages', 'icon') ? 'icon' : null,
                ]));

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable('workflow_templates')) {
            Schema::table('workflow_templates', function (Blueprint $table) {
                if (Schema::hasColumn('workflow_templates', 'source_template_id')) {
                    $table->dropForeign(['source_template_id']);
                }

                $dropColumns = array_values(array_filter([
                    Schema::hasColumn('workflow_templates', 'signature_hash') ? 'signature_hash' : null,
                    Schema::hasColumn('workflow_templates', 'deprecated_at') ? 'deprecated_at' : null,
                    Schema::hasColumn('workflow_templates', 'source_template_id') ? 'source_template_id' : null,
                ]));

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            /** @var Collection<int, object> $indexes */
            $indexes = collect(DB::select('PRAGMA index_list('.$table.')'));

            return $indexes->contains(fn (object $row) => ($row->name ?? null) === $index);
        }

        $database = $connection->getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};