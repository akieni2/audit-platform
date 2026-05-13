<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projection_integrity_checks')) {
            return;
        }

        Schema::create('projection_integrity_checks', function (Blueprint $table) {
            $table->id();
            $table->string('projection_type', 120)->index();
            $table->string('scope_type', 80)->nullable()->index();
            $table->string('scope_id', 80)->nullable()->index();
            $table->string('status', 32)->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->string('expected_signature', 64)->nullable();
            $table->string('actual_signature', 64)->nullable();
            $table->unsignedInteger('mismatch_count')->default(0);
            $table->json('expected_payload')->nullable();
            $table->json('actual_payload')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projection_integrity_checks');
    }
};
