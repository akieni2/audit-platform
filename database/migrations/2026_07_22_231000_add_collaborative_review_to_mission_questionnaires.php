<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->string('review_status')->default('draft')->after('lifecycle_status')->index();
            $table->timestamp('review_requested_at')->nullable()->after('review_status');
            $table->timestamp('adopted_at')->nullable()->after('review_requested_at');
            $table->foreignId('adopted_by')->nullable()->after('adopted_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('questionnaire_template_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('questionnaire_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['questionnaire_template_id', 'reviewer_id'], 'questionnaire_review_reviewer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_template_reviews');
        Schema::table('questionnaire_templates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('adopted_by');
            $table->dropColumn(['review_status', 'review_requested_at', 'adopted_at']);
        });
    }
};
