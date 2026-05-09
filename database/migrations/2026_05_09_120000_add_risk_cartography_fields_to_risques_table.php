<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risques', function (Blueprint $table) {
            $table->string('proprietaire')->nullable()->after('description');
            $table->string('departement')->nullable()->after('proprietaire');
            $table->date('date_revue')->nullable()->after('departement');
            $table->text('plan_mitigation')->nullable()->after('date_revue');
            $table->string('statut_risque', 32)->default('identifie')->after('plan_mitigation');

            $table->string('criticite_inherent', 16)->nullable()->after('score_inherent');
            $table->string('criticite_residuel', 16)->nullable()->after('score_residuel');
        });
    }

    public function down(): void
    {
        Schema::table('risques', function (Blueprint $table) {
            $table->dropColumn([
                'proprietaire',
                'departement',
                'date_revue',
                'plan_mitigation',
                'statut_risque',
                'criticite_inherent',
                'criticite_residuel',
            ]);
        });
    }
};
