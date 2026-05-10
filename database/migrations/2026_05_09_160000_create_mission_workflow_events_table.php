<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('from_status', 64)->nullable();
            $table->string('to_status', 64)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['mission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_workflow_events');
    }
};
