<?php

namespace App\Services\Forms;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FormPublishingService
{
    public function ensureEditableDraft(FormTemplate $template, ?User $actor = null): FormTemplate
    {
        if (! $template->isImmutable()) {
            return $template;
        }

        $rootId = $this->lineageRootId($template);

        $existingDraft = FormTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->where('lifecycle_status', FormTemplate::STATUS_DRAFT)
            ->orderByDesc('version')
            ->first();

        if ($existingDraft instanceof FormTemplate) {
            return $existingDraft;
        }

        return DB::transaction(function () use ($template, $actor, $rootId) {
            $template->loadMissing(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);

            $draft = FormTemplate::query()->create([
                'name' => $template->name,
                'slug' => $this->uniqueDraftSlug($template, $rootId),
                'description' => $template->description,
                'component_key' => $template->component_key,
                'department_scope' => $template->department_scope,
                'active' => false,
                'version' => $this->nextVersionNumber($rootId),
                'lifecycle_status' => FormTemplate::STATUS_DRAFT,
                'signature_hash' => null,
                'published_at' => null,
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId,
                'created_by' => $actor?->id ?? $template->created_by,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ]);

            foreach ($template->fields as $field) {
                $draftField = FormField::query()->create([
                    'form_template_id' => $draft->id,
                    'field_key' => $field->field_key,
                    'label' => $field->label,
                    'help_text' => $field->help_text,
                    'field_type' => $field->field_type,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->getRawOriginal('default_value') ?? $field->default_value,
                    'configuration_json' => $field->configuration_json,
                    'validation_rules_json' => $field->validation_rules_json,
                    'conditional_rules_json' => $field->conditional_rules_json,
                    'sort_order' => $field->sort_order,
                    'is_required' => $field->is_required,
                    'is_repeatable' => $field->is_repeatable,
                    'active' => $field->active,
                    'source_field_id' => $field->id,
                ]);

                foreach ($field->options as $option) {
                    FormFieldOption::query()->create([
                        'form_field_id' => $draftField->id,
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => $option->sort_order,
                        'is_default' => $option->is_default,
                        'source_option_id' => $option->id,
                        'metadata' => $option->metadata,
                    ]);
                }
            }

            return $draft->fresh(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);
        });
    }

    public function publish(FormTemplate $template, ?User $actor = null): FormTemplate
    {
        $template->loadMissing(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);
        $this->validateStructure($template);

        $rootId = $this->lineageRootId($template);
        $signature = $this->signatureFor($template);
        $version = max((int) ($template->version ?? 1), $this->nextVersionNumber($rootId, $template->id));

        return DB::transaction(function () use ($template, $actor, $rootId, $signature, $version) {
            FormTemplate::query()
                ->where(function ($query) use ($rootId) {
                    $query->whereKey($rootId)
                        ->orWhere('source_template_id', $rootId);
                })
                ->where('lifecycle_status', FormTemplate::STATUS_PUBLISHED)
                ->whereKeyNot($template->id)
                ->update([
                    'lifecycle_status' => FormTemplate::STATUS_DEPRECATED,
                    'active' => false,
                    'deprecated_at' => now(),
                    'updated_at' => now(),
                ]);

            $template->forceFill([
                'version' => $version,
                'active' => true,
                'lifecycle_status' => FormTemplate::STATUS_PUBLISHED,
                'signature_hash' => $signature,
                'published_at' => now(),
                'deprecated_at' => null,
                'archived_at' => null,
                'source_template_id' => $rootId === $template->id ? null : $rootId,
                'updated_by' => $actor?->id ?? $template->updated_by,
            ])->save();

            return $template->fresh(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);
        });
    }

    public function archive(FormTemplate $template, ?User $actor = null): FormTemplate
    {
        $template->forceFill([
            'active' => false,
            'lifecycle_status' => FormTemplate::STATUS_ARCHIVED,
            'archived_at' => now(),
            'updated_by' => $actor?->id ?? $template->updated_by,
        ])->save();

        return $template->fresh();
    }

    /**
     * @param  list<array<string, mixed>>  $optionRows
     */
    public function syncFieldOptions(FormField $field, array $optionRows): void
    {
        $existing = $field->options()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($optionRows) as $index => $row) {
            $option = null;
            $optionId = (int) ($row['id'] ?? 0);
            if ($optionId > 0) {
                $option = $existing->get($optionId);
            }

            $payload = [
                'label' => (string) ($row['label'] ?? ''),
                'value' => (string) ($row['value'] ?? $row['label'] ?? ''),
                'sort_order' => $index,
                'is_default' => (bool) ($row['is_default'] ?? false),
                'metadata' => $row['metadata'] ?? null,
            ];

            if ($option instanceof FormFieldOption) {
                $option->update($payload);
                $keptIds[] = (int) $option->id;
                continue;
            }

            $created = $field->options()->create($payload);
            $keptIds[] = (int) $created->id;
        }

        if ($keptIds !== []) {
            $field->options()->whereNotIn('id', $keptIds)->delete();
            return;
        }

        $field->options()->delete();
    }

    public function validateStructure(FormTemplate $template): void
    {
        $template->loadMissing(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);
        $fields = $template->fields->where('active', true)->values();

        if ($fields->isEmpty()) {
            throw new InvalidArgumentException('Le formulaire doit contenir au moins un champ actif avant publication.');
        }

        foreach ($fields as $field) {
            if (! in_array($field->field_type, FormField::fieldTypes(), true)) {
                throw new InvalidArgumentException(sprintf('Le champ "%s" utilise un type non supporté.', $field->label));
            }

            if ($field->usesOptions() && $field->options->whereNull('deleted_at')->isEmpty()) {
                throw new InvalidArgumentException(sprintf('Le champ "%s" doit définir des options.', $field->label));
            }

            if (blank($field->field_key) || blank($field->label)) {
                throw new InvalidArgumentException('Chaque champ doit définir une clé et un libellé.');
            }
        }
    }

    public function signatureFor(FormTemplate $template): string
    {
        $template->loadMissing(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);

        $payload = [
            'template' => [
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => $template->description,
                'component_key' => $template->component_key,
                'department_scope' => $template->department_scope ?? [],
                'version' => (int) ($template->version ?? 1),
            ],
            'fields' => $template->fields->sortBy('sort_order')->values()->map(function (FormField $field) {
                return [
                    'field_key' => $field->field_key,
                    'label' => $field->label,
                    'help_text' => $field->help_text,
                    'field_type' => $field->field_type,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->getRawOriginal('default_value'),
                    'configuration_json' => $field->configuration_json ?? [],
                    'validation_rules_json' => $field->validation_rules_json ?? [],
                    'conditional_rules_json' => $field->conditional_rules_json ?? [],
                    'sort_order' => (int) $field->sort_order,
                    'is_required' => (bool) $field->is_required,
                    'is_repeatable' => (bool) $field->is_repeatable,
                    'active' => (bool) $field->active,
                    'options' => $field->options->sortBy('sort_order')->values()->map(fn (FormFieldOption $option) => [
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => (int) $option->sort_order,
                        'is_default' => (bool) $option->is_default,
                        'metadata' => $option->metadata ?? [],
                    ])->all(),
                ];
            })->all(),
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    private function lineageRootId(FormTemplate $template): int
    {
        return (int) ($template->source_template_id ?: $template->id);
    }

    private function nextVersionNumber(int $rootId, ?int $ignoreTemplateId = null): int
    {
        return (int) FormTemplate::query()
            ->where(function ($query) use ($rootId) {
                $query->whereKey($rootId)
                    ->orWhere('source_template_id', $rootId);
            })
            ->when($ignoreTemplateId !== null, fn ($query) => $query->whereKeyNot($ignoreTemplateId))
            ->max('version') + 1;
    }

    private function uniqueDraftSlug(FormTemplate $template, int $rootId): string
    {
        $nextVersion = $this->nextVersionNumber($rootId);
        $base = Str::slug($template->name).'-v'.$nextVersion.'-draft';
        $slug = $base;

        while (FormTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
