<?php

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->addRiskRegistryColumns();
        $this->addIdentifiedRiskReviewColumns();
        $this->normalizeCriticalities();
        $this->normalizeLifecycles();
        $this->backfillRegistryFields();
    }

    public function down(): void
    {
        // Sprint migration intentionally keeps progressive compatibility; no destructive rollback.
    }

    private function addRiskRegistryColumns(): void
    {
        if (! Schema::hasTable('risques')) {
            return;
        }

        Schema::table('risques', function (Blueprint $table) {
            if (! Schema::hasColumn('risques', 'risk_uuid')) {
                $table->uuid('risk_uuid')->nullable()->after('identified_risk_id');
            }
            if (! Schema::hasColumn('risques', 'risk_reference')) {
                $table->string('risk_reference', 64)->nullable()->after('risk_uuid');
            }
            if (! Schema::hasColumn('risques', 'criticality')) {
                $table->string('criticality', 32)->nullable()->after('lifecycle_status');
            }
            if (! Schema::hasColumn('risques', 'detected_at')) {
                $table->timestamp('detected_at')->nullable()->after('criticality');
            }
            if (! Schema::hasColumn('risques', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('detected_at');
            }
            if (! Schema::hasColumn('risques', 'promoted_at')) {
                $table->timestamp('promoted_at')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('risques', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('promoted_at');
            }
            if (! Schema::hasColumn('risques', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('closed_at');
            }
            if (! Schema::hasColumn('risques', 'source_identified_risk_id')) {
                $table->unsignedBigInteger('source_identified_risk_id')->nullable()->after('identified_risk_id');
            }
            if (! Schema::hasColumn('risques', 'source_entretien_id')) {
                $table->unsignedBigInteger('source_entretien_id')->nullable()->after('source_identified_risk_id');
            }
            if (! Schema::hasColumn('risques', 'source_question_id')) {
                $table->unsignedBigInteger('source_question_id')->nullable()->after('source_entretien_id');
            }
            if (! Schema::hasColumn('risques', 'residual_score')) {
                $table->unsignedSmallInteger('residual_score')->nullable()->after('score_residuel');
            }
            if (! Schema::hasColumn('risques', 'inherent_score')) {
                $table->unsignedSmallInteger('inherent_score')->nullable()->after('score_inherent');
            }
            if (! Schema::hasColumn('risques', 'heatmap_x')) {
                $table->unsignedTinyInteger('heatmap_x')->nullable()->after('residual_score');
            }
            if (! Schema::hasColumn('risques', 'heatmap_y')) {
                $table->unsignedTinyInteger('heatmap_y')->nullable()->after('heatmap_x');
            }
            if (! Schema::hasColumn('risques', 'owner_user_id') && Schema::hasTable('users')) {
                $table->foreignId('owner_user_id')->nullable()->after('owner_department_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('risques', 'reviewed_by') && Schema::hasTable('users')) {
                $table->foreignId('reviewed_by')->nullable()->after('owner_user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('risques', 'promoted_by') && Schema::hasTable('users')) {
                $table->foreignId('promoted_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('risques', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('promoted_by');
            }
            if (! Schema::hasColumn('risques', 'closure_notes')) {
                $table->text('closure_notes')->nullable()->after('approval_notes');
            }
            if (! Schema::hasColumn('risques', 'metadata')) {
                $table->json('metadata')->nullable()->after('closure_notes');
            }
            if (! Schema::hasColumn('risques', 'promotion_signature')) {
                $table->string('promotion_signature', 64)->nullable()->after('metadata');
            }
        });

        Schema::table('risques', function (Blueprint $table) {
            $table->index('risk_reference', 'risques_risk_reference_index');
            $table->index('criticality', 'risques_criticality_index');
            $table->index('owner_user_id', 'risques_owner_user_id_index');
            $table->index('risk_uuid', 'risques_risk_uuid_index');
            $table->index('source_identified_risk_id', 'risques_source_identified_risk_id_index');
        });
    }

    private function addIdentifiedRiskReviewColumns(): void
    {
        if (! Schema::hasTable('identified_risks')) {
            return;
        }

        Schema::table('identified_risks', function (Blueprint $table) {
            if (! Schema::hasColumn('identified_risks', 'submitted_for_review_at')) {
                $table->timestamp('submitted_for_review_at')->nullable()->after('validated_by_human');
            }
            if (! Schema::hasColumn('identified_risks', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('identified_risks', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('identified_risks', 'rejected_by') && Schema::hasTable('users')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('identified_risks', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('identified_risks', 'rejection_notes')) {
                $table->text('rejection_notes')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('identified_risks', 'owner_user_id') && Schema::hasTable('users')) {
                $table->foreignId('owner_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('identified_risks', 'owner_department_id') && Schema::hasTable('departments')) {
                $table->foreignId('owner_department_id')->nullable()->after('owner_user_id')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('identified_risks', 'metadata')) {
                $table->json('metadata')->nullable()->after('owner_department_id');
            }
        });
    }

    private function normalizeCriticalities(): void
    {
        $map = CriticalityLevel::legacyMap();

        foreach ([
            ['table' => 'identified_risks', 'columns' => ['criticality']],
            ['table' => 'risques', 'columns' => ['criticality', 'criticite_inherent', 'criticite_residuel', 'severity']],
        ] as $definition) {
            if (! Schema::hasTable($definition['table'])) {
                continue;
            }

            foreach ($definition['columns'] as $column) {
                if (! Schema::hasColumn($definition['table'], $column)) {
                    continue;
                }

                foreach ($map as $legacy => $canonical) {
                    DB::table($definition['table'])
                        ->where($column, $legacy)
                        ->update([$column => $canonical]);
                }
            }
        }
    }

    private function normalizeLifecycles(): void
    {
        $map = [
            'reviewed' => RiskLifecycleStatus::UnderReview->value,
            'qualified' => RiskLifecycleStatus::UnderReview->value,
            'approved' => RiskLifecycleStatus::Validated->value,
        ];

        foreach (['identified_risks', 'risques'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'lifecycle_status')) {
                continue;
            }

            foreach ($map as $legacy => $canonical) {
                DB::table($table)
                    ->where('lifecycle_status', $legacy)
                    ->update(['lifecycle_status' => $canonical]);
            }
        }
    }

    private function backfillRegistryFields(): void
    {
        if (Schema::hasTable('risques')) {
            DB::table('risques')
                ->whereNull('source_identified_risk_id')
                ->whereNotNull('identified_risk_id')
                ->update(['source_identified_risk_id' => DB::raw('identified_risk_id')]);

            DB::table('risques')
                ->whereNull('inherent_score')
                ->whereNotNull('score_inherent')
                ->update(['inherent_score' => DB::raw('score_inherent')]);

            DB::table('risques')
                ->whereNull('residual_score')
                ->whereNotNull('score_residuel')
                ->update(['residual_score' => DB::raw('score_residuel')]);

            DB::table('risques')
                ->whereNull('criticality')
                ->update(['criticality' => DB::raw('COALESCE(criticite_residuel, criticite_inherent)')]);

            DB::table('risques')
                ->whereNull('detected_at')
                ->update(['detected_at' => DB::raw('created_at')]);

            DB::table('risques')
                ->where('lifecycle_status', RiskLifecycleStatus::Promoted->value)
                ->whereNull('promoted_at')
                ->update(['promoted_at' => DB::raw('COALESCE(updated_at, created_at)')]);

            DB::table('risques')
                ->where('lifecycle_status', RiskLifecycleStatus::Closed->value)
                ->whereNull('closed_at')
                ->update(['closed_at' => DB::raw('COALESCE(updated_at, created_at)')]);

            DB::table('risques')
                ->orderBy('id')
                ->select(['id', 'risk_uuid', 'risk_reference', 'promotion_signature', 'identified_risk_id', 'source_identified_risk_id', 'created_at', 'description'])
                ->chunkById(100, function ($risques): void {
                    foreach ($risques as $risque) {
                        $sourceId = $risque->source_identified_risk_id ?: $risque->identified_risk_id ?: $risque->id;
                        $payload = [];

                        if ($risque->risk_uuid === null) {
                            $payload['risk_uuid'] = (string) Str::uuid();
                        }

                        if ($risque->risk_reference === null) {
                            $year = $risque->created_at ? date('Y', strtotime((string) $risque->created_at)) : now()->format('Y');
                            $payload['risk_reference'] = sprintf('RISK-%s-LEG-%s', $year, $sourceId);
                        }

                        if ($risque->promotion_signature === null) {
                            $payload['promotion_signature'] = sha1(implode('|', [
                                $sourceId,
                                (string) ($risque->description ?? ''),
                                (string) ($risque->created_at ?? ''),
                            ]));
                        }

                        if ($payload !== []) {
                            DB::table('risques')->where('id', $risque->id)->update($payload);
                        }
                    }
                });
        }

        if (Schema::hasTable('identified_risks')) {
            DB::table('identified_risks')
                ->where('lifecycle_status', RiskLifecycleStatus::UnderReview->value)
                ->whereNull('submitted_for_review_at')
                ->update(['submitted_for_review_at' => DB::raw('COALESCE(reviewed_at, updated_at, created_at)')]);
        }
    }
};
