<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::create('questionnaires', function (Blueprint $table) {

$table->id();

$table->unsignedBigInteger('entretien_id');

$table->string('titre');

$table->text('description')->nullable();

$table->timestamps();

});

}

public function down(): void
{
Schema::dropIfExists('questionnaires');
}

};
