<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('reference', 128)->nullable()->after('organisation');
            $table->text('objet')->nullable()->after('reference');
            $table->string('periode_audit', 255)->nullable()->after('objet');
            $table->string('ordre_mission_reference', 128)->nullable()->after('periode_audit');
            $table->date('date_ordre_mission')->nullable()->after('ordre_mission_reference');
            $table->text('observations_generales')->nullable()->after('date_ordre_mission');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn([
                'reference',
                'objet',
                'periode_audit',
                'ordre_mission_reference',
                'date_ordre_mission',
                'observations_generales',
            ]);
        });
    }
};
