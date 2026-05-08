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
        Schema::create('audit_programmes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_plan_id');
            $table->text('procedure');
            $table->string('type'); // entretien / test / verification
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_programmes');
    }
};
