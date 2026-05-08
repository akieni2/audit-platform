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
        Schema::create('actions_correctives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('risque_id');

            $table->text('description');

            $table->string('responsable')->nullable();

            $table->date('date_echeance')->nullable();

            $table->string('statut')->default('ouvert');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions_correctives');
    }
};
