<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('profile_photo');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('password_changed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->boolean('mfa_enabled')->default(false)->after('locked_until');
            $table->text('mfa_recovery_codes')->nullable()->after('mfa_enabled');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->after('ip');
            $table->json('metadata')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'metadata']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'last_login_at',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'mfa_enabled',
                'mfa_recovery_codes',
            ]);
        });
    }
};
