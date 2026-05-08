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
        Schema::create('controles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('risque_id');

            $table->enum('type', [
                'preventif',
                'detectif',
                'correctif'
            ]);

            $table->text('description');

            $table->enum('efficacite', [
                'faible',
                'moyenne',
                'forte'
            ]);

            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controles');
    }
};
