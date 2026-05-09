<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'profile_photo')) {
                if (Schema::hasColumn('users', 'telephone')) {
                    $table->string('profile_photo')->nullable()->after('telephone');
                } else {
                    $table->string('profile_photo')->nullable();
                }
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('profile_photo');
            }

            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('password_changed_at');
            }

            if (! Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }

            if (! Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('locked_until');
            }

            if (! Schema::hasColumn('users', 'mfa_recovery_codes')) {
                $table->text('mfa_recovery_codes')->nullable()->after('mfa_enabled');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip');
            }

            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $cols[] = 'user_agent';
            }
            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $cols[] = 'metadata';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach ([
                'profile_photo',
                'last_login_at',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'mfa_enabled',
                'mfa_recovery_codes',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
