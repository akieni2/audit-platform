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
        Schema::table('actions_correctives', function (Blueprint $table) {
             $table->unsignedBigInteger('recommendation_library_id')->nullable();
            $table->foreign('recommendation_library_id')
                  ->references('id')
                  ->on('recommendation_libraries')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actions_correctives', function (Blueprint $table) {
            $table->dropColumn('recommendation_library_id');
        });
    }
};
