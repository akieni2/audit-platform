<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risques', function (Blueprint $table) {
            $table->foreignId('source_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('target_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('shared')->default(false);
            $table->boolean('cross_department')->default(false);
            $table->boolean('escalated')->default(false);
            $table->string('severity', 32)->nullable();
            $table->text('treatment_plan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('risques', function (Blueprint $table) {
            $table->dropForeign(['source_department_id']);
            $table->dropForeign(['target_department_id']);
            $table->dropForeign(['owner_department_id']);
            $table->dropColumn([
                'source_department_id',
                'target_department_id',
                'owner_department_id',
                'shared',
                'cross_department',
                'escalated',
                'severity',
                'treatment_plan',
            ]);
        });
    }
};
