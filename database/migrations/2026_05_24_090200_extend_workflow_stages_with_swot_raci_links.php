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
            if (! Schema::hasColumn('workflow_stages', 'swot_template_id')) {
                $table->foreignId('swot_template_id')->nullable()->after('form_template_id')->constrained('swot_templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('workflow_stages', 'raci_template_id')) {
                $table->foreignId('raci_template_id')->nullable()->after('swot_template_id')->constrained('raci_templates')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('workflow_stages')) {
            return;
        }

        Schema::table('workflow_stages', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_stages', 'raci_template_id')) {
                $table->dropForeign(['raci_template_id']);
            }

            if (Schema::hasColumn('workflow_stages', 'swot_template_id')) {
                $table->dropForeign(['swot_template_id']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('workflow_stages', 'swot_template_id') ? 'swot_template_id' : null,
                Schema::hasColumn('workflow_stages', 'raci_template_id') ? 'raci_template_id' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
