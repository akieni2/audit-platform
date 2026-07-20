<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alignement des statuts de mission sur le workflow institutionnel DGCPT/COPRI.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('missions', 'mission_status')) {
            return;
        }

        DB::table('missions')->where('mission_status', 'draft')->update(['mission_status' => 'brouillon']);
        DB::table('missions')->where('mission_status', 'closed')->update(['mission_status' => 'clôturée']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('missions', 'mission_status')) {
            return;
        }

        DB::table('missions')->where('mission_status', 'brouillon')->update(['mission_status' => 'draft']);
        DB::table('missions')->where('mission_status', 'clôturée')->update(['mission_status' => 'closed']);
    }
};
