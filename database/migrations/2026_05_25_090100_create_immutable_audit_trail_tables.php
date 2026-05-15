<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('immutable_audit_events')) {
            Schema::create('immutable_audit_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event_type')->index();
                $table->string('module')->index();
                $table->string('resource_type')->nullable()->index();
                $table->unsignedBigInteger('resource_id')->nullable()->index();
                $table->string('action_signature')->nullable()->index();
                $table->string('integrity_hash')->index();
                $table->string('previous_hash')->nullable()->index();
                $table->text('description')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['module', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('runtime_security_events')) {
            Schema::create('runtime_security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
                $table->string('severity')->default('info')->index();
                $table->string('event_type')->index();
                $table->string('threat_level')->nullable()->index();
                $table->boolean('blocked')->default(false);
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('data_access_events')) {
            Schema::create('data_access_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->nullable()->constrained('tenant_contexts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('access_type')->index();
                $table->string('resource_type')->index();
                $table->unsignedBigInteger('resource_id')->nullable()->index();
                $table->string('outcome')->default('allowed')->index();
                $table->json('metadata')->nullable();
                $table->timestamp('accessed_at')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_events');
        Schema::dropIfExists('runtime_security_events');
        Schema::dropIfExists('immutable_audit_events');
    }
};
