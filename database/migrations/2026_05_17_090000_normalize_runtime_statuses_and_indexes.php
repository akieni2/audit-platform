<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('missions') && Schema::hasColumn('missions', 'mission_status')) {
            DB::table('missions')->where('mission_status', 'draft')->update(['mission_status' => 'brouillon']);
            DB::table('missions')->where('mission_status', 'closed')->update(['mission_status' => 'clôturée']);

            Schema::table('missions', function (Blueprint $table) {
                $table->string('mission_status', 64)->default('brouillon')->change();
                $table->index('mission_status', 'missions_mission_status_index');
            });
        }

        if (Schema::hasTable('identified_risks') && Schema::hasColumn('identified_risks', 'criticality')) {
            foreach ($this->criticalityMappings() as $target => $values) {
                DB::table('identified_risks')
                    ->whereIn('criticality', $values)
                    ->update(['criticality' => $target]);
            }
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'mission_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->index('mission_id', 'services_mission_id_index');
            });
        }

        if (Schema::hasTable('entretiens')) {
            Schema::table('entretiens', function (Blueprint $table) {
                if (Schema::hasColumn('entretiens', 'mission_id')) {
                    $table->index('mission_id', 'entretiens_mission_id_index');
                }
                if (Schema::hasColumn('entretiens', 'service_id')) {
                    $table->index('service_id', 'entretiens_service_id_index');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('approval_status', 'users_approval_status_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('missions') && Schema::hasColumn('missions', 'mission_status')) {
            Schema::table('missions', function (Blueprint $table) {
                $table->dropIndex('missions_mission_status_index');
                $table->string('mission_status', 64)->default('draft')->change();
            });
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'mission_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropIndex('services_mission_id_index');
            });
        }

        if (Schema::hasTable('entretiens')) {
            Schema::table('entretiens', function (Blueprint $table) {
                if (Schema::hasColumn('entretiens', 'mission_id')) {
                    $table->dropIndex('entretiens_mission_id_index');
                }
                if (Schema::hasColumn('entretiens', 'service_id')) {
                    $table->dropIndex('entretiens_service_id_index');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_approval_status_index');
            });
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function criticalityMappings(): array
    {
        return [
            'faible' => ['faible', 'Faible', 'low', 'Low', 'basse', 'Basse'],
            'moyen' => ['moyen', 'Moyen', 'moyenne', 'Moyenne', 'medium', 'Medium', 'moderate', 'Moderate'],
            'eleve' => ['eleve', 'Eleve', 'élevée', 'Élevée', 'elevée', 'Elevée', 'high', 'High'],
            'critique' => ['critique', 'Critique', 'critical', 'Critical'],
        ];
    }
};
