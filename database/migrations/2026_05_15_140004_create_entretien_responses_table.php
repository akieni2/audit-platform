<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entretien_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entretien_id')->constrained()->cascadeOnDelete();
            $table->foreignId('questionnaire_question_id')->constrained()->restrictOnDelete();
            $table->boolean('answer_boolean')->nullable()->comment('null = N/A pour boolean_na');
            $table->longText('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->text('observation')->nullable();
            $table->json('uploaded_documents_metadata')->nullable();
            $table->text('detected_risk')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['entretien_id', 'questionnaire_question_id'],
    'entretien_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entretien_responses');
    }
};
