<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdt_courses', function (Blueprint $table) {
            $table->text('review_observation')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('validated_by');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('cfdt_courses', fn (Blueprint $table) => $table->dropColumn(['review_observation', 'submitted_at', 'reviewed_at']));
    }
};
