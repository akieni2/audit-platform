<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_events')) {
            return;
        }

        Schema::create('business_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 160)->index();
            $table->string('aggregate_type', 80)->nullable()->index();
            $table->string('aggregate_id', 120)->nullable()->index();
            $table->unsignedBigInteger('mission_id')->nullable()->index();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->string('causation_id', 64)->nullable()->index();
            $table->string('idempotency_key', 160)->nullable();
            $table->string('status', 32)->default('recorded')->index();
            $table->string('queue', 64)->nullable();
            $table->json('payload')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['event_name', 'idempotency_key'], 'business_events_event_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_events');
    }
};
