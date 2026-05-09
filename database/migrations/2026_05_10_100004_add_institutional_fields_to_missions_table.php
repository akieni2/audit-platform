<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('auditeur_id')->constrained()->nullOnDelete();
            $table->string('mission_type', 64)->nullable()->after('department_id');
            $table->string('mission_status', 64)->default('draft')->after('mission_type');
            $table->string('priority', 32)->nullable()->after('mission_status');
            $table->string('sensitivity_level', 32)->nullable()->after('priority');
            $table->string('confidentiality_level', 32)->nullable()->after('sensitivity_level');
            $table->foreignId('supervising_department_id')->nullable()->after('confidentiality_level')->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['supervising_department_id']);
            $table->dropColumn([
                'department_id',
                'mission_type',
                'mission_status',
                'priority',
                'sensitivity_level',
                'confidentiality_level',
                'supervising_department_id',
            ]);
        });
    }
};
