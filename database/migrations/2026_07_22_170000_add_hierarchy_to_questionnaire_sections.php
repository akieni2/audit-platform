<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_sections', function (Blueprint $table): void {
            $table->string('section_type', 32)->default('theme')->after('description');
            $table->foreignId('parent_section_id')->nullable()->after('section_type')
                ->constrained('questionnaire_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_sections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_section_id');
            $table->dropColumn('section_type');
        });
    }
};
