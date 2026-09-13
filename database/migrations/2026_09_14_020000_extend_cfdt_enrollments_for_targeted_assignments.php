<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdt_enrollments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('assigned_at')->index();
            $table->uuid('invitation_token')->nullable()->unique()->after('expires_at');
            $table->timestamp('notified_at')->nullable()->after('invitation_token');
            $table->string('assignment_source', 30)->nullable()->after('notified_at');
            $table->json('assignment_context')->nullable()->after('assignment_source');
        });
    }

    public function down(): void
    {
        Schema::table('cfdt_enrollments', function (Blueprint $table) {
            $table->dropUnique(['invitation_token']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expires_at', 'invitation_token', 'notified_at', 'assignment_source', 'assignment_context']);
        });
    }
};
