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
        Schema::create('risk_libraries', function (Blueprint $table) {
            $table->id();
            $table->string('categorie');
            $table->string('processus');

            $table->string('titre');

            $table->text('description');

            $table->integer('impact_default');
            $table->integer('probabilite_default');
            $table->unsignedBigInteger('risk_library_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_libraries');
    }
};
