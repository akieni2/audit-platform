<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_contexts')) {
            Schema::create('tenant_contexts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('tenant_key')->unique();
                $table->string('isolation_mode')->default('strict')->index();
                $table->string('cache_prefix', 64)->nullable();
                $table->boolean('active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_security_policies')) {
            Schema::create('tenant_security_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->constrained('tenant_contexts')->cascadeOnDelete();
                $table->boolean('mfa_required')->default(false);
                $table->boolean('strict_session_binding')->default(true);
                $table->unsignedInteger('max_session_minutes')->default(120);
                $table->boolean('signed_actions_required')->default(true);
                $table->boolean('api_access_enabled')->default(true);
                $table->json('allowed_modules')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_audit_scopes')) {
            Schema::create('tenant_audit_scopes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_context_id')->constrained('tenant_contexts')->cascadeOnDelete();
                $table->string('module')->index();
                $table->boolean('immutable_trail_enabled')->default(true);
                $table->unsignedInteger('retention_days')->default(2555);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['tenant_context_id', 'module'], 'tenant_audit_scopes_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_audit_scopes');
        Schema::dropIfExists('tenant_security_policies');
        Schema::dropIfExists('tenant_contexts');
    }
};
