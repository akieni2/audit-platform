<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->foreignId('mission_id')
                ->nullable()
                ->after('methodology_template_id')
                ->constrained('missions')
                ->nullOnDelete();
            $table->index(['mission_id', 'active'], 'questionnaire_templates_mission_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->dropIndex('questionnaire_templates_mission_active_index');
            $table->dropConstrainedForeignId('mission_id');
        });
    }
};
