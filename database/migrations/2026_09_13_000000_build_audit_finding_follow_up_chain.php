<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entretiens', function (Blueprint $table): void {
            $table->string('responsable_nom')->nullable()->change();
            $table->string('role')->nullable()->change();
            $table->string('chef_hierarchique')->nullable()->change();
        });

        Schema::table('constats', function (Blueprint $table): void {
            $table->string('reference', 64)->nullable()->unique()->after('id');
            $table->foreignId('entretien_response_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->foreignId('questionnaire_question_id')->nullable()->after('entretien_response_id')->constrained()->nullOnDelete();
            $table->text('criterion')->nullable()->after('questionnaire_question_id');
            $table->longText('condition_observed')->nullable()->after('criterion');
            $table->string('status', 40)->default('draft')->after('recommandation');
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->foreignId('created_by')->nullable()->after('version')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('validated_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->json('metadata')->nullable()->after('validated_at');
            $table->index(['mission_id', 'status']);
        });

        DB::table('constats')->orderBy('id')->each(function (object $row): void {
            DB::table('constats')->where('id', $row->id)->update([
                'reference' => sprintf('CST-%06d', $row->id),
                'condition_observed' => $row->description,
            ]);
        });

        Schema::create('constat_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('constat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['constat_id', 'mission_document_id']);
        });

        Schema::create('constat_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('constat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage', 40);
            $table->string('decision', 40);
            $table->text('comment')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['constat_id', 'stage']);
        });

        Schema::create('constat_auditee_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('constat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 40);
            $table->longText('observation');
            $table->longText('proposed_action')->nullable();
            $table->foreignId('proposed_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('proposed_due_date')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();
        });

        Schema::table('identified_risks', function (Blueprint $table): void {
            $table->foreignId('constat_id')->nullable()->after('mission_id')->constrained()->nullOnDelete();
            $table->unique('constat_id');
        });

        Schema::create('audit_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('constat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('identified_risk_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('description');
            $table->string('priority', 32)->default('medium');
            $table->string('status', 40)->default('draft');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['mission_id', 'status']);
            $table->index(['owner_department_id', 'due_date']);
        });

        Schema::table('actions_correctives', function (Blueprint $table): void {
            $table->unsignedBigInteger('risque_id')->nullable()->change();
            $table->foreignId('audit_recommendation_id')->nullable()->after('risque_id')->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->after('responsable')->constrained('users')->nullOnDelete();
            $table->foreignId('owner_department_id')->nullable()->after('owner_user_id')->constrained('departments')->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('statut');
            $table->timestamp('started_at')->nullable()->after('progress_percent');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('closure_requested_at')->nullable()->after('completed_at');
            $table->foreignId('closure_validated_by')->nullable()->after('closure_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('closure_validated_at')->nullable()->after('closure_validated_by');
            $table->text('closure_comment')->nullable()->after('closure_validated_at');
            $table->json('metadata')->nullable()->after('closure_comment');
            $table->index(['statut', 'date_echeance']);
        });

        Schema::create('action_corrective_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('action_corrective_id')->constrained('actions_correctives')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent');
            $table->string('status', 40);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('action_corrective_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('action_corrective_id')->constrained('actions_correctives')->cascadeOnDelete();
            $table->foreignId('mission_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['action_corrective_id', 'mission_document_id'], 'action_evidence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_corrective_evidences');
        Schema::dropIfExists('action_corrective_updates');

        Schema::table('actions_correctives', function (Blueprint $table): void {
            foreach (['audit_recommendation_id', 'owner_user_id', 'owner_department_id', 'closure_validated_by'] as $column) {
                $table->dropConstrainedForeignId($column);
            }
            $table->dropIndex(['statut', 'date_echeance']);
            $table->dropColumn(['progress_percent', 'started_at', 'completed_at', 'closure_requested_at', 'closure_validated_at', 'closure_comment', 'metadata']);
            $table->unsignedBigInteger('risque_id')->nullable(false)->change();
        });

        Schema::dropIfExists('audit_recommendations');
        Schema::table('identified_risks', function (Blueprint $table): void {
            $table->dropUnique(['constat_id']);
            $table->dropConstrainedForeignId('constat_id');
        });
        Schema::dropIfExists('constat_auditee_responses');
        Schema::dropIfExists('constat_reviews');
        Schema::dropIfExists('constat_evidences');

        Schema::table('constats', function (Blueprint $table): void {
            $table->dropIndex(['mission_id', 'status']);
            foreach (['entretien_response_id', 'questionnaire_question_id', 'created_by', 'reviewed_by', 'validated_by'] as $column) {
                $table->dropConstrainedForeignId($column);
            }
            $table->dropUnique(['reference']);
            $table->dropColumn(['reference', 'criterion', 'condition_observed', 'status', 'version', 'reviewed_at', 'validated_at', 'metadata']);
        });

        DB::table('entretiens')->whereNull('responsable_nom')->update(['responsable_nom' => 'Non renseigné']);
        DB::table('entretiens')->whereNull('role')->update(['role' => 'Non renseigné']);
        DB::table('entretiens')->whereNull('chef_hierarchique')->update(['chef_hierarchique' => 'Non renseigné']);
        Schema::table('entretiens', function (Blueprint $table): void {
            $table->string('responsable_nom')->nullable(false)->change();
            $table->string('role')->nullable(false)->change();
            $table->string('chef_hierarchique')->nullable(false)->change();
        });
    }
};
