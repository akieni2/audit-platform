<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identified_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entretien_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('questionnaire_question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 128)->nullable();
            $table->string('probability', 32)->nullable();
            $table->string('impact', 32)->nullable();
            $table->string('criticality', 32)->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->boolean('validated_by_human')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identified_risks');
    }
};
