<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_audit_consolidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->text('synthesis')->nullable();
            $table->string('global_risk_level', 64)->nullable();
            $table->text('key_findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('generated_by_ai')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mission_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_audit_consolidations');
    }
};
