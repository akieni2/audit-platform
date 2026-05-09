<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('department_id')->constrained('roles')->nullOnDelete();
            $table->boolean('active')->default(true)->after('password');
            $table->string('position')->nullable()->after('fonction');
            $table->string('phone', 32)->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['department_id', 'role_id', 'active', 'position', 'phone']);
        });
    }
};
