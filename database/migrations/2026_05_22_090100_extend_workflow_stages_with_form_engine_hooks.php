<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_stages')) {
            return;
        }

        Schema::table('workflow_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('workflow_stages', 'form_template_id')) {
                $table->foreignId('form_template_id')
                    ->nullable()
                    ->after('questionnaire_template_id')
                    ->constrained('form_templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('workflow_stages', 'component_key')) {
                $table->string('component_key', 80)
                    ->nullable()
                    ->after('ui_component');
            }
        });

        Schema::table('workflow_stages', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_stages', 'form_template_id')) {
                $table->index('form_template_id');
            }

            if (Schema::hasColumn('workflow_stages', 'component_key')) {
                $table->index('component_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_stages')) {
            return;
        }

        Schema::table('workflow_stages', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_stages', 'form_template_id')) {
                $table->dropForeign(['form_template_id']);
                $table->dropIndex(['form_template_id']);
            }

            if (Schema::hasColumn('workflow_stages', 'component_key')) {
                $table->dropIndex(['component_key']);
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('workflow_stages', 'form_template_id') ? 'form_template_id' : null,
                Schema::hasColumn('workflow_stages', 'component_key') ? 'component_key' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
