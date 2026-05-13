<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mission_risk_projections')) {
            return;
        }

        Schema::table('mission_risk_projections', function (Blueprint $table) {
            if (! Schema::hasColumn('mission_risk_projections', 'source_signature')) {
                $table->string('source_signature', 64)->nullable()->after('residual_heatmap');
            }
            if (! Schema::hasColumn('mission_risk_projections', 'source_record_count')) {
                $table->unsignedInteger('source_record_count')->default(0)->after('source_signature');
            }
            if (! Schema::hasColumn('mission_risk_projections', 'refresh_count')) {
                $table->unsignedInteger('refresh_count')->default(0)->after('source_record_count');
            }
            if (! Schema::hasColumn('mission_risk_projections', 'integrity_status')) {
                $table->string('integrity_status', 32)->default('unknown')->after('refresh_count');
            }
            if (! Schema::hasColumn('mission_risk_projections', 'last_integrity_checked_at')) {
                $table->timestamp('last_integrity_checked_at')->nullable()->after('integrity_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('mission_risk_projections')) {
            return;
        }

        Schema::table('mission_risk_projections', function (Blueprint $table) {
            foreach ([
                'source_signature',
                'source_record_count',
                'refresh_count',
                'integrity_status',
                'last_integrity_checked_at',
            ] as $column) {
                if (Schema::hasColumn('mission_risk_projections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
