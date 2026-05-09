<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                if (Schema::hasColumn('users', 'mfa_recovery_codes')) {
                    $table->boolean('must_change_password')->default(false)->after('mfa_recovery_codes');
                } else {
                    $table->boolean('must_change_password')->default(false);
                }
            }

            if (! Schema::hasColumn('users', 'password_expires_at')) {
                if (Schema::hasColumn('users', 'must_change_password')) {
                    $table->timestamp('password_expires_at')->nullable()->after('must_change_password');
                } else {
                    $table->timestamp('password_expires_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'password_expires_at')) {
                $cols[] = 'password_expires_at';
            }
            if (Schema::hasColumn('users', 'must_change_password')) {
                $cols[] = 'must_change_password';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
