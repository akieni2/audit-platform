<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production : migrer les anciennes données `phone` vers `telephone`, puis supprimer `phone`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            return;
        }

        DB::table('users')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $tel = $row->telephone ?? null;
                $phone = $row->phone ?? null;
                $telEmpty = $tel === null || $tel === '';
                $phoneFilled = $phone !== null && $phone !== '';

                if ($telEmpty && $phoneFilled) {
                    DB::table('users')->where('id', $row->id)->update(['telephone' => $phone]);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'phone')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telephone')) {
                $table->string('phone', 32)->nullable()->after('telephone');
            } else {
                $table->string('phone', 32)->nullable();
            }
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $tel = $row->telephone ?? null;
                $telFilled = $tel !== null && $tel !== '';

                if ($telFilled) {
                    DB::table('users')->where('id', $row->id)->update(['phone' => $tel]);
                }
            }
        });
    }
};
