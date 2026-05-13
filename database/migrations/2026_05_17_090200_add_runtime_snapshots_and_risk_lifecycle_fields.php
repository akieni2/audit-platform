<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entretiens')) {
            Schema::table('entretiens', function (Blueprint $table) {
                if (! Schema::hasColumn('entretiens', 'questionnaire_snapshot')) {
                    $table->json('questionnaire_snapshot')->nullable()->after('questionnaire_template_id');
                }
                if (! Schema::hasColumn('entretiens', 'questionnaire_snapshot_version')) {
                    $table->unsignedInteger('questionnaire_snapshot_version')->nullable()->after('questionnaire_snapshot');
                }
                if (! Schema::hasColumn('entretiens', 'questionnaire_snapshot_hash')) {
                    $table->string('questionnaire_snapshot_hash', 64)->nullable()->after('questionnaire_snapshot_version');
                }
                if (! Schema::hasColumn('entretiens', 'questionnaire_snapshot_taken_at')) {
                    $table->timestamp('questionnaire_snapshot_taken_at')->nullable()->after('questionnaire_snapshot_hash');
                }
            });
        }

        if (Schema::hasTable('identified_risks')) {
            $addedSourceSignature = false;
            $addedLifecycleStatus = false;

            Schema::table('identified_risks', function (Blueprint $table) use (&$addedSourceSignature, &$addedLifecycleStatus) {
                if (! Schema::hasColumn('identified_risks', 'source_signature')) {
                    $table->string('source_signature', 64)->nullable()->after('questionnaire_question_id');
                    $addedSourceSignature = true;
                }
                if (! Schema::hasColumn('identified_risks', 'lifecycle_status')) {
                    $table->string('lifecycle_status', 32)->default('detected')->after('criticality');
                    $addedLifecycleStatus = true;
                }
                if (! Schema::hasColumn('identified_risks', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('validated_by_human')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('identified_risks', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (! Schema::hasColumn('identified_risks', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('identified_risks', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
                if (! Schema::hasColumn('identified_risks', 'promoted_at')) {
                    $table->timestamp('promoted_at')->nullable()->after('approved_at');
                }
                if (! Schema::hasColumn('identified_risks', 'promotion_notes')) {
                    $table->text('promotion_notes')->nullable()->after('promoted_at');
                }
            });

            if ($addedSourceSignature) {
                Schema::table('identified_risks', function (Blueprint $table) {
                    $table->index('source_signature');
                });
            }

            if ($addedLifecycleStatus) {
                Schema::table('identified_risks', function (Blueprint $table) {
                    $table->index('lifecycle_status');
                });
            }
        }

        if (Schema::hasTable('risques')) {
            $addedIdentifiedRiskId = false;
            $addedRiskLifecycleStatus = false;

            Schema::table('risques', function (Blueprint $table) use (&$addedIdentifiedRiskId, &$addedRiskLifecycleStatus) {
                if (! Schema::hasColumn('risques', 'identified_risk_id')) {
                    $table->unsignedBigInteger('identified_risk_id')->nullable()->after('actif_id');
                    $addedIdentifiedRiskId = true;
                }
                if (! Schema::hasColumn('risques', 'lifecycle_status')) {
                    $table->string('lifecycle_status', 32)->default('promoted')->after('statut_risque');
                    $addedRiskLifecycleStatus = true;
                }
            });

            if ($addedIdentifiedRiskId) {
                Schema::table('risques', function (Blueprint $table) {
                    $table->index('identified_risk_id');
                });
            }

            if ($addedRiskLifecycleStatus) {
                Schema::table('risques', function (Blueprint $table) {
                    $table->index('lifecycle_status');
                });
            }

            DB::table('risques')
                ->whereNull('lifecycle_status')
                ->update(['lifecycle_status' => 'promoted']);

            DB::table('risques')
                ->where('statut_risque', 'mitige')
                ->update(['lifecycle_status' => 'mitigated']);

            DB::table('risques')
                ->where('statut_risque', 'ferme')
                ->update(['lifecycle_status' => 'closed']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('risques')) {
            Schema::table('risques', function (Blueprint $table) {
                if (Schema::hasColumn('risques', 'identified_risk_id')) {
                    $table->dropIndex(['identified_risk_id']);
                    $table->dropColumn('identified_risk_id');
                }
                if (Schema::hasColumn('risques', 'lifecycle_status')) {
                    $table->dropIndex(['lifecycle_status']);
                    $table->dropColumn('lifecycle_status');
                }
            });
        }

        if (Schema::hasTable('identified_risks')) {
            Schema::table('identified_risks', function (Blueprint $table) {
                if (Schema::hasColumn('identified_risks', 'source_signature')) {
                    $table->dropIndex(['source_signature']);
                    $table->dropColumn('source_signature');
                }
                if (Schema::hasColumn('identified_risks', 'lifecycle_status')) {
                    $table->dropIndex(['lifecycle_status']);
                    $table->dropColumn('lifecycle_status');
                }
                foreach (['reviewed_by', 'approved_by'] as $column) {
                    if (Schema::hasColumn('identified_risks', $column)) {
                        $table->dropForeign([$column]);
                    }
                }
                foreach (['reviewed_by', 'reviewed_at', 'approved_by', 'approved_at', 'promoted_at', 'promotion_notes'] as $column) {
                    if (Schema::hasColumn('identified_risks', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('entretiens')) {
            Schema::table('entretiens', function (Blueprint $table) {
                foreach ([
                    'questionnaire_snapshot',
                    'questionnaire_snapshot_version',
                    'questionnaire_snapshot_hash',
                    'questionnaire_snapshot_taken_at',
                ] as $column) {
                    if (Schema::hasColumn('entretiens', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
