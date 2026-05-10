<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('supervisor_user_id')->nullable()->after('active')->constrained('users')->nullOnDelete();
            $table->string('accent_color', 32)->nullable()->after('supervisor_user_id');
            $table->string('logo_path')->nullable()->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['supervisor_user_id']);
            $table->dropColumn(['supervisor_user_id', 'accent_color', 'logo_path']);
        });
    }
};
