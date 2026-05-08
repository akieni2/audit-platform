<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('risques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actif_id');

            $table->string('description');

             // RISQUE INHERENT
            $table->integer('impact_inherent');
            $table->integer('probabilite_inherent');
            $table->integer('score_inherent');

            // RISQUE RESIDUEL
            $table->integer('impact_residuel')->nullable();
            $table->integer('probabilite_residuel')->nullable();
            $table->integer('score_residuel')->nullable();

            $table->string('niveau')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risques');
    }
};
