<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (! Schema::hasColumn('departments', 'parent_department_id')) {
                    $table->foreignId('parent_department_id')->nullable()->after('supervisor_user_id')->constrained('departments')->nullOnDelete();
                }
                if (! Schema::hasColumn('departments', 'governance_scope')) {
                    $table->string('governance_scope')->nullable()->after('parent_department_id')->index();
                }
                if (! Schema::hasColumn('departments', 'default_methodology_template_id')) {
                    $table->foreignId('default_methodology_template_id')->nullable()->after('governance_scope')->constrained('methodology_templates')->nullOnDelete();
                }
                if (! Schema::hasColumn('departments', 'default_taxonomy_id')) {
                    $table->foreignId('default_taxonomy_id')->nullable()->after('default_methodology_template_id')->constrained('taxonomies')->nullOnDelete();
                }
                if (! Schema::hasColumn('departments', 'executive_visibility')) {
                    $table->boolean('executive_visibility')->default(false)->after('default_taxonomy_id');
                }
                if (! Schema::hasColumn('departments', 'intelligence_profile')) {
                    $table->json('intelligence_profile')->nullable()->after('executive_visibility');
                }
            });
        }

        if (Schema::hasTable('workflow_templates')) {
            Schema::table('workflow_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('workflow_templates', 'methodology_template_id')) {
                    $table->foreignId('methodology_template_id')->nullable()->after('department_id')->constrained('methodology_templates')->nullOnDelete();
                }
                if (! Schema::hasColumn('workflow_templates', 'visibility_scope')) {
                    $table->string('visibility_scope')->nullable()->after('status')->index();
                }
                if (! Schema::hasColumn('workflow_templates', 'sharing_mode')) {
                    $table->string('sharing_mode')->nullable()->after('visibility_scope')->index();
                }
                if (! Schema::hasColumn('workflow_templates', 'is_global_template')) {
                    $table->boolean('is_global_template')->default(false)->after('sharing_mode');
                }
                if (! Schema::hasColumn('workflow_templates', 'is_private_template')) {
                    $table->boolean('is_private_template')->default(false)->after('is_global_template');
                }
                if (! Schema::hasColumn('workflow_templates', 'governance_tags')) {
                    $table->json('governance_tags')->nullable()->after('is_private_template');
                }
            });
        }

        if (Schema::hasTable('form_templates')) {
            Schema::table('form_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('form_templates', 'methodology_template_id')) {
                    $table->foreignId('methodology_template_id')->nullable()->after('component_key')->constrained('methodology_templates')->nullOnDelete();
                }
                if (! Schema::hasColumn('form_templates', 'visibility_scope')) {
                    $table->string('visibility_scope')->nullable()->after('department_scope')->index();
                }
                if (! Schema::hasColumn('form_templates', 'sharing_mode')) {
                    $table->string('sharing_mode')->nullable()->after('visibility_scope')->index();
                }
                if (! Schema::hasColumn('form_templates', 'is_global_template')) {
                    $table->boolean('is_global_template')->default(false)->after('sharing_mode');
                }
                if (! Schema::hasColumn('form_templates', 'is_private_template')) {
                    $table->boolean('is_private_template')->default(false)->after('is_global_template');
                }
                if (! Schema::hasColumn('form_templates', 'governance_tags')) {
                    $table->json('governance_tags')->nullable()->after('is_private_template');
                }
            });
        }

        if (Schema::hasTable('questionnaire_templates')) {
            Schema::table('questionnaire_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('questionnaire_templates', 'methodology_template_id')) {
                    $table->foreignId('methodology_template_id')->nullable()->after('mission_type')->constrained('methodology_templates')->nullOnDelete();
                }
                if (! Schema::hasColumn('questionnaire_templates', 'visibility_scope')) {
                    $table->string('visibility_scope')->nullable()->after('department_scope')->index();
                }
                if (! Schema::hasColumn('questionnaire_templates', 'sharing_mode')) {
                    $table->string('sharing_mode')->nullable()->after('visibility_scope')->index();
                }
                if (! Schema::hasColumn('questionnaire_templates', 'is_global_template')) {
                    $table->boolean('is_global_template')->default(false)->after('sharing_mode');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'is_private_template')) {
                    $table->boolean('is_private_template')->default(false)->after('is_global_template');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'governance_tags')) {
                    $table->json('governance_tags')->nullable()->after('is_private_template');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questionnaire_templates')) {
            Schema::table('questionnaire_templates', function (Blueprint $table) {
                foreach (['methodology_template_id', 'visibility_scope', 'sharing_mode', 'is_global_template', 'is_private_template', 'governance_tags'] as $column) {
                    if (Schema::hasColumn('questionnaire_templates', $column)) {
                        if ($column === 'methodology_template_id') {
                            $table->dropForeign(['methodology_template_id']);
                        }
                    }
                }

                $columns = array_values(array_filter([
                    Schema::hasColumn('questionnaire_templates', 'methodology_template_id') ? 'methodology_template_id' : null,
                    Schema::hasColumn('questionnaire_templates', 'visibility_scope') ? 'visibility_scope' : null,
                    Schema::hasColumn('questionnaire_templates', 'sharing_mode') ? 'sharing_mode' : null,
                    Schema::hasColumn('questionnaire_templates', 'is_global_template') ? 'is_global_template' : null,
                    Schema::hasColumn('questionnaire_templates', 'is_private_template') ? 'is_private_template' : null,
                    Schema::hasColumn('questionnaire_templates', 'governance_tags') ? 'governance_tags' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('form_templates')) {
            Schema::table('form_templates', function (Blueprint $table) {
                if (Schema::hasColumn('form_templates', 'methodology_template_id')) {
                    $table->dropForeign(['methodology_template_id']);
                }

                $columns = array_values(array_filter([
                    Schema::hasColumn('form_templates', 'methodology_template_id') ? 'methodology_template_id' : null,
                    Schema::hasColumn('form_templates', 'visibility_scope') ? 'visibility_scope' : null,
                    Schema::hasColumn('form_templates', 'sharing_mode') ? 'sharing_mode' : null,
                    Schema::hasColumn('form_templates', 'is_global_template') ? 'is_global_template' : null,
                    Schema::hasColumn('form_templates', 'is_private_template') ? 'is_private_template' : null,
                    Schema::hasColumn('form_templates', 'governance_tags') ? 'governance_tags' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('workflow_templates')) {
            Schema::table('workflow_templates', function (Blueprint $table) {
                if (Schema::hasColumn('workflow_templates', 'methodology_template_id')) {
                    $table->dropForeign(['methodology_template_id']);
                }

                $columns = array_values(array_filter([
                    Schema::hasColumn('workflow_templates', 'methodology_template_id') ? 'methodology_template_id' : null,
                    Schema::hasColumn('workflow_templates', 'visibility_scope') ? 'visibility_scope' : null,
                    Schema::hasColumn('workflow_templates', 'sharing_mode') ? 'sharing_mode' : null,
                    Schema::hasColumn('workflow_templates', 'is_global_template') ? 'is_global_template' : null,
                    Schema::hasColumn('workflow_templates', 'is_private_template') ? 'is_private_template' : null,
                    Schema::hasColumn('workflow_templates', 'governance_tags') ? 'governance_tags' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (Schema::hasColumn('departments', 'parent_department_id')) {
                    $table->dropForeign(['parent_department_id']);
                }
                if (Schema::hasColumn('departments', 'default_methodology_template_id')) {
                    $table->dropForeign(['default_methodology_template_id']);
                }
                if (Schema::hasColumn('departments', 'default_taxonomy_id')) {
                    $table->dropForeign(['default_taxonomy_id']);
                }

                $columns = array_values(array_filter([
                    Schema::hasColumn('departments', 'parent_department_id') ? 'parent_department_id' : null,
                    Schema::hasColumn('departments', 'governance_scope') ? 'governance_scope' : null,
                    Schema::hasColumn('departments', 'default_methodology_template_id') ? 'default_methodology_template_id' : null,
                    Schema::hasColumn('departments', 'default_taxonomy_id') ? 'default_taxonomy_id' : null,
                    Schema::hasColumn('departments', 'executive_visibility') ? 'executive_visibility' : null,
                    Schema::hasColumn('departments', 'intelligence_profile') ? 'intelligence_profile' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
