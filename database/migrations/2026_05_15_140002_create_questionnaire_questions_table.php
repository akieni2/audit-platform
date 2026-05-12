<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_section_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64)->nullable();
            $table->text('question');
            $table->text('help_text')->nullable();
            $table->string('question_type', 32);
            $table->boolean('required')->default(false);
            $table->boolean('allows_observation')->default(true);
            $table->boolean('allows_risk_detection')->default(false);
            $table->text('expected_documents')->nullable();
            $table->string('risk_category', 128)->nullable();
            $table->string('risk_level', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_questions');
    }
};
