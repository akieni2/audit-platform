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
        Schema::create('entretiens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mission_id');
            $table->unsignedBigInteger('service_id');

            $table->string('responsable_nom');
            $table->string('role');
            $table->string('chef_hierarchique');

            $table->string('auditeur')->nullable();
            $table->date('date_entretien')->nullable();

            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entretiens');
    }
};
