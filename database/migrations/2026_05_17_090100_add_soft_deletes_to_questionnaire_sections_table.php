<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questionnaire_sections') && ! Schema::hasColumn('questionnaire_sections', 'deleted_at')) {
            Schema::table('questionnaire_sections', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questionnaire_sections') && Schema::hasColumn('questionnaire_sections', 'deleted_at')) {
            Schema::table('questionnaire_sections', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
