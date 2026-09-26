<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdt_course_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('cfdt_courses')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->string('path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('required')->default(true);
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('cfdt_resource_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('cfdt_enrollments')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('cfdt_course_resources')->cascadeOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdt_resource_progress');
        Schema::dropIfExists('cfdt_course_resources');
    }
};
