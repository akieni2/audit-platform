<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'responsable')) {
                $table->string('responsable')->nullable()->after('nom');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'code')) {
                $table->string('code', 64)->nullable()->after('mission_id');
            }
            if (! Schema::hasColumn('services', 'chef_service_user_id')) {
                $table->foreignId('chef_service_user_id')->nullable()->after('code')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('services', 'chef_service_nom')) {
                $table->string('chef_service_nom')->nullable()->after('chef_service_user_id');
            }
            if (! Schema::hasColumn('services', 'chef_service_fonction')) {
                $table->string('chef_service_fonction')->nullable()->after('chef_service_nom');
            }
            if (! Schema::hasColumn('services', 'chef_service_email')) {
                $table->string('chef_service_email')->nullable()->after('chef_service_fonction');
            }
            if (! Schema::hasColumn('services', 'chef_service_telephone')) {
                $table->string('chef_service_telephone', 64)->nullable()->after('chef_service_email');
            }
            if (! Schema::hasColumn('services', 'service_type')) {
                $table->string('service_type', 64)->nullable()->after('chef_service_telephone');
            }
            if (! Schema::hasColumn('services', 'service_scope')) {
                $table->string('service_scope', 255)->nullable()->after('service_type');
            }
            if (! Schema::hasColumn('services', 'active')) {
                $table->boolean('active')->default(true)->after('service_scope');
            }
            if (! Schema::hasColumn('services', 'observations')) {
                $table->text('observations')->nullable()->after('description');
            }
            if (! Schema::hasColumn('services', 'audit_priority')) {
                $table->string('audit_priority', 32)->nullable()->after('observations');
            }
            if (! Schema::hasColumn('services', 'risk_level')) {
                $table->string('risk_level', 32)->nullable()->after('audit_priority');
            }
            if (! Schema::hasColumn('services', 'audit_status')) {
                $table->string('audit_status', 32)->default('pending')->after('risk_level');
            }
            if (! Schema::hasColumn('services', 'metadata')) {
                $table->json('metadata')->nullable()->after('audit_status');
            }
            if (! Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $cols = [
                'code', 'chef_service_user_id', 'chef_service_nom', 'chef_service_fonction',
                'chef_service_email', 'chef_service_telephone', 'service_type', 'service_scope',
                'active', 'observations', 'audit_priority', 'risk_level', 'audit_status', 'metadata', 'deleted_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('services', $col)) {
                    if ($col === 'chef_service_user_id') {
                        $table->dropForeign(['chef_service_user_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
