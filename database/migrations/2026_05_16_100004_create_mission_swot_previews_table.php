<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_swot_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('placeholder');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mission_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_swot_previews');
    }
};
