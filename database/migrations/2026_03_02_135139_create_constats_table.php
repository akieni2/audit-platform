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
        Schema::create('constats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mission_id');

            $table->unsignedBigInteger('service_id')->nullable();

            $table->text('description');

            $table->text('cause')->nullable();

            $table->text('consequence')->nullable();

            $table->string('gravite')->nullable();

            $table->text('recommandation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constats');
    }
};
