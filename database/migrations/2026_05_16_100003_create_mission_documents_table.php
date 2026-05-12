<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entretien_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->string('disk', 32)->default('local');
            $table->string('path', 1024);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('category', 64)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mission_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_documents');
    }
};
