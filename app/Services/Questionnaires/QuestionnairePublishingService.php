<?php

namespace App\Services\Questionnaires;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class QuestionnairePublishingService
{
    public function ensureEditableDraft(QuestionnaireTemplate $template, ?User $actor = null): QuestionnaireTemplate
    {
        if (! $template->isImmutable()) {
            return $template;
        }

        $rootId = $this->lineageRootId($template);

        $existingDraft = QuestionnaireTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->where('lifecycle_status', QuestionnaireTemplate::STATUS_DRAFT)
            ->orderByDesc('version')
            ->first();

        if ($existingDraft instanceof QuestionnaireTemplate) {
            return $existingDraft;
        }

        return DB::transaction(function () use ($template, $actor, $rootId) {
            $template->loadMissing(['sections.questions' => fn ($query) => $query->orderBy('sort_order')]);

            $draft = QuestionnaireTemplate::query()->create([
                'name' => $template->name,
                'slug' => $this->uniqueDraftSlug($template, $rootId),
                'description' => $template->description,
                'mission_type' => $template->mission_type,
                'department_scope' => $template->department_scope,
                'active' => false,
                'version' => $this->nextVersionNumber($rootId),
                'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                'signature_hash' => null,
                'published_at' => null,
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId,
                'created_by' => $actor?->id ?? $template->created_by,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ]);

            foreach ($template->sections as $section) {
                $draftSection = QuestionnaireSection::query()->create([
                    'questionnaire_template_id' => $draft->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'sort_order' => $section->sort_order,
                    'source_section_id' => $section->id,
                ]);

                foreach ($section->questions as $question) {
                    QuestionnaireQuestion::query()->create([
                        'questionnaire_section_id' => $draftSection->id,
                        'code' => $question->code,
                        'question' => $question->question,
                        'help_text' => $question->help_text,
                        'question_type' => $question->question_type,
                        'required' => $question->required,
                        'allows_observation' => $question->allows_observation,
                        'allows_risk_detection' => $question->allows_risk_detection,
                        'expected_documents' => $question->expected_documents,
                        'risk_category' => $question->risk_category,
                        'risk_level' => $question->risk_level,
                        'sort_order' => $question->sort_order,
                        'active' => $question->active,
                        'metadata' => $question->metadata,
                        'source_question_id' => $question->id,
                    ]);
                }
            }

            return $draft->fresh(['sections.questions' => fn ($query) => $query->orderBy('sort_order')]);
        });
    }

    public function publish(QuestionnaireTemplate $template, ?User $actor = null): QuestionnaireTemplate
    {
        $template->loadMissing(['sections.questions' => fn ($query) => $query->orderBy('sort_order')]);
        $this->validateStructure($template);

        $rootId = $this->lineageRootId($template);
        $signature = $this->signatureFor($template);
        $version = max((int) ($template->version ?? 1), $this->nextVersionNumber($rootId, $template->id));

        return DB::transaction(function () use ($template, $actor, $rootId, $signature, $version) {
            QuestionnaireTemplate::query()
                ->where(function ($query) use ($rootId) {
                    $query->whereKey($rootId)
                        ->orWhere('source_template_id', $rootId);
                })
                ->where('lifecycle_status', QuestionnaireTemplate::STATUS_PUBLISHED)
                ->whereKeyNot($template->id)
                ->update([
                    'lifecycle_status' => QuestionnaireTemplate::STATUS_DEPRECATED,
                    'active' => false,
                    'deprecated_at' => now(),
                    'updated_at' => now(),
                ]);

            $template->forceFill([
                'version' => $version,
                'active' => true,
                'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
                'signature_hash' => $signature,
                'published_at' => now(),
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId === $template->id ? null : $rootId,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ])->save();

            return $template->fresh(['sections.questions' => fn ($query) => $query->orderBy('sort_order')]);
        });
    }

    public function archive(QuestionnaireTemplate $template, ?User $actor = null): QuestionnaireTemplate
    {
        $template->forceFill([
            'active' => false,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_ARCHIVED,
            'archived_at' => now(),
            'updated_by' => $actor?->id ?? $template->updated_by,
        ])->save();

        return $template->fresh();
    }

    public function validateStructure(QuestionnaireTemplate $template): void
    {
        $sections = $template->sections->values();
        if ($sections->isEmpty()) {
            throw new InvalidArgumentException('Le template doit contenir au moins une section avant publication.');
        }

        foreach ($sections as $section) {
            $questions = $section->questions->where('active', true)->values();
            if ($questions->isEmpty()) {
                throw new InvalidArgumentException(sprintf(
                    'La section "%s" doit contenir au moins une question active.',
                    $section->title
                ));
            }

            foreach ($questions as $question) {
                if (! in_array($question->question_type, QuestionnaireQuestion::questionTypes(), true)) {
                    throw new InvalidArgumentException(sprintf(
                        'La question "%s" utilise un type non supporté.',
                        Str::limit($question->question, 80)
                    ));
                }

                $metadata = is_array($question->metadata) ? $question->metadata : [];
                $options = array_values(array_filter($metadata['options'] ?? [], fn ($value) => filled($value)));
                if (in_array($question->question_type, [
                    QuestionnaireQuestion::TYPE_SELECT,
                    QuestionnaireQuestion::TYPE_RADIO,
                    QuestionnaireQuestion::TYPE_CHECKBOX,
                ], true) && $options === []) {
                    throw new InvalidArgumentException(sprintf(
                        'La question "%s" doit définir des options.',
                        Str::limit($question->question, 80)
                    ));
                }

                $criticality = data_get($metadata, 'risk_mapping.default_criticality');
                if ($criticality !== null && CriticalityLevel::fromMixed((string) $criticality) === null) {
                    throw new InvalidArgumentException(sprintf(
                        'La question "%s" contient une criticité par défaut invalide.',
                        Str::limit($question->question, 80)
                    ));
                }
            }
        }
    }

    public function signatureFor(QuestionnaireTemplate $template): string
    {
        $template->loadMissing(['sections.questions' => fn ($query) => $query->orderBy('sort_order')]);

        $payload = [
            'template' => [
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => $template->description,
                'mission_type' => $template->mission_type,
                'department_scope' => $template->department_scope ?? [],
                'version' => (int) ($template->version ?? 1),
            ],
            'sections' => $template->sections->sortBy('sort_order')->values()->map(function (QuestionnaireSection $section) {
                return [
                    'title' => (string) $section->title,
                    'description' => $section->description,
                    'sort_order' => (int) $section->sort_order,
                    'questions' => $section->questions->sortBy('sort_order')->values()->map(function (QuestionnaireQuestion $question) {
                        return [
                            'code' => $question->code,
                            'question' => (string) $question->question,
                            'help_text' => $question->help_text,
                            'question_type' => (string) $question->question_type,
                            'required' => (bool) $question->required,
                            'allows_observation' => (bool) $question->allows_observation,
                            'allows_risk_detection' => (bool) $question->allows_risk_detection,
                            'expected_documents' => $question->expected_documents,
                            'risk_category' => $question->risk_category,
                            'risk_level' => $question->risk_level,
                            'sort_order' => (int) $question->sort_order,
                            'active' => (bool) $question->active,
                            'metadata' => $question->metadata ?? [],
                        ];
                    })->all(),
                ];
            })->all(),
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function lineageRootId(QuestionnaireTemplate $template): int
    {
        return (int) ($template->source_template_id ?: $template->id);
    }

    private function nextVersionNumber(int $rootId, ?int $ignoreTemplateId = null): int
    {
        return (int) QuestionnaireTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->when($ignoreTemplateId !== null, fn ($query) => $query->whereKeyNot($ignoreTemplateId))
            ->max('version') + 1;
    }

    private function uniqueDraftSlug(QuestionnaireTemplate $template, int $rootId): string
    {
        $nextVersion = $this->nextVersionNumber($rootId);
        $base = Str::slug($template->name).'-v'.$nextVersion.'-draft';
        $slug = $base;

        while (QuestionnaireTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
