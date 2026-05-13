<?php

use App\Models\QuestionnaireTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questionnaire_templates')) {
            $addedLifecycleStatus = false;
            $addedSourceTemplateId = false;

            Schema::table('questionnaire_templates', function (Blueprint $table) use (&$addedLifecycleStatus, &$addedSourceTemplateId) {
                if (! Schema::hasColumn('questionnaire_templates', 'lifecycle_status')) {
                    $table->string('lifecycle_status', 32)
                        ->default(QuestionnaireTemplate::STATUS_DRAFT)
                        ->after('version');
                    $addedLifecycleStatus = true;
                }
                if (! Schema::hasColumn('questionnaire_templates', 'signature_hash')) {
                    $table->string('signature_hash', 64)->nullable()->after('lifecycle_status');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('signature_hash');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'deprecated_at')) {
                    $table->timestamp('deprecated_at')->nullable()->after('published_at');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('deprecated_at');
                }
                if (! Schema::hasColumn('questionnaire_templates', 'source_template_id')) {
                    $table->unsignedBigInteger('source_template_id')->nullable()->after('archived_at');
                    $addedSourceTemplateId = true;
                }
            });

            if ($addedLifecycleStatus) {
                Schema::table('questionnaire_templates', function (Blueprint $table) {
                    $table->index('lifecycle_status');
                });
            }

            if ($addedSourceTemplateId) {
                Schema::table('questionnaire_templates', function (Blueprint $table) {
                    $table->index('source_template_id');
                });
            }

            DB::table('questionnaire_templates')
                ->where('active', true)
                ->where(function ($query) {
                    $query->whereNull('lifecycle_status')
                        ->orWhere('lifecycle_status', QuestionnaireTemplate::STATUS_DRAFT);
                })
                ->update([
                    'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
                    'published_at' => DB::raw('COALESCE(published_at, updated_at, created_at)'),
                ]);

            DB::table('questionnaire_templates')
                ->where('active', false)
                ->whereNull('lifecycle_status')
                ->update([
                    'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                ]);
        }

        if (Schema::hasTable('questionnaire_sections')) {
            $addedSourceSectionId = false;

            Schema::table('questionnaire_sections', function (Blueprint $table) use (&$addedSourceSectionId) {
                if (! Schema::hasColumn('questionnaire_sections', 'source_section_id')) {
                    $table->unsignedBigInteger('source_section_id')->nullable()->after('sort_order');
                    $addedSourceSectionId = true;
                }
            });

            if ($addedSourceSectionId) {
                Schema::table('questionnaire_sections', function (Blueprint $table) {
                    $table->index('source_section_id');
                });
            }
        }

        if (Schema::hasTable('questionnaire_questions')) {
            $addedSourceQuestionId = false;

            Schema::table('questionnaire_questions', function (Blueprint $table) use (&$addedSourceQuestionId) {
                if (! Schema::hasColumn('questionnaire_questions', 'source_question_id')) {
                    $table->unsignedBigInteger('source_question_id')->nullable()->after('metadata');
                    $addedSourceQuestionId = true;
                }
            });

            if ($addedSourceQuestionId) {
                Schema::table('questionnaire_questions', function (Blueprint $table) {
                    $table->index('source_question_id');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questionnaire_questions')) {
            Schema::table('questionnaire_questions', function (Blueprint $table) {
                if (Schema::hasColumn('questionnaire_questions', 'source_question_id')) {
                    $table->dropIndex(['source_question_id']);
                    $table->dropColumn('source_question_id');
                }
            });
        }

        if (Schema::hasTable('questionnaire_sections')) {
            Schema::table('questionnaire_sections', function (Blueprint $table) {
                if (Schema::hasColumn('questionnaire_sections', 'source_section_id')) {
                    $table->dropIndex(['source_section_id']);
                    $table->dropColumn('source_section_id');
                }
            });
        }

        if (Schema::hasTable('questionnaire_templates')) {
            Schema::table('questionnaire_templates', function (Blueprint $table) {
                if (Schema::hasColumn('questionnaire_templates', 'lifecycle_status')) {
                    $table->dropIndex(['lifecycle_status']);
                    $table->dropColumn('lifecycle_status');
                }
                if (Schema::hasColumn('questionnaire_templates', 'source_template_id')) {
                    $table->dropIndex(['source_template_id']);
                    $table->dropColumn('source_template_id');
                }
                foreach (['signature_hash', 'published_at', 'deprecated_at', 'archived_at'] as $column) {
                    if (Schema::hasColumn('questionnaire_templates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
