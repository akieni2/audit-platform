<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_risk_projections')) {
            return;
        }

        Schema::create('mission_risk_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('intake_detected_count')->default(0);
            $table->unsignedInteger('intake_reviewed_count')->default(0);
            $table->unsignedInteger('intake_qualified_count')->default(0);
            $table->unsignedInteger('intake_approved_count')->default(0);
            $table->unsignedInteger('intake_promoted_count')->default(0);
            $table->unsignedInteger('official_count')->default(0);
            $table->unsignedInteger('official_critical_count')->default(0);
            $table->unsignedInteger('official_residual_critical_count')->default(0);
            $table->json('inherent_heatmap')->nullable();
            $table->json('residual_heatmap')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_risk_projections');
    }
};
