<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entretiens', function (Blueprint $table) {
            $table->foreignId('questionnaire_template_id')
                ->nullable()
                ->after('service_id')
                ->constrained('questionnaire_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('entretiens', function (Blueprint $table) {
            $table->dropForeign(['questionnaire_template_id']);
            $table->dropColumn('questionnaire_template_id');
        });
    }
};
