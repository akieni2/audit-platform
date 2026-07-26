<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('gec_menu_enabled')->nullable()->after('copri_menu_enabled');
            $table->boolean('administrative_work_menu_enabled')->nullable()->after('gec_menu_enabled');
        });

        Schema::create('correspondence_records', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->string('sender');
            $table->string('recipient')->nullable();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('document_type')->nullable();
            $table->enum('confidentiality', ['normal', 'confidential', 'secret'])->default('normal');
            $table->enum('urgency', ['low', 'standard', 'normal', 'urgent', 'very_urgent'])->default('normal');
            $table->enum('status', ['registered', 'assigned', 'in_progress', 'answered', 'closed', 'archived'])->default('registered');
            $table->timestamp('received_at');
            $table->timestamp('deadline_at')->nullable();
            $table->foreignId('current_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('current_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_path')->nullable();
            $table->string('qr_token', 64)->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'deadline_at']);
            $table->index(['direction', 'received_at']);
        });

        Schema::create('correspondence_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correspondence_record_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['correspondence_record_id', 'occurred_at']);
        });

        Schema::create('administrative_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correspondence_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->enum('status', ['draft', 'assigned', 'in_progress', 'submitted', 'validated', 'closed'])->default('assigned');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_at']);
            $table->index(['department_id', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_tasks');
        Schema::dropIfExists('correspondence_movements');
        Schema::dropIfExists('correspondence_records');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['gec_menu_enabled', 'administrative_work_menu_enabled']);
        });
    }
};
