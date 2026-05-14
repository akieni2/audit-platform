@php
    $submissionStatus = $form['current_submission']?->status;
    $wizardData = $wizard ?? app(\App\Services\Forms\FormWizardService::class)->build($form);
    $autosaveData = $autosave ?? app(\App\Services\Forms\RuntimeAutosaveService::class)->build($form['current_submission'] ?? null, $instance, $stage);
    $validationSummary = app(\App\Services\Forms\DynamicValidationSummaryService::class)->summarize($form, $errors);
    $attachmentFields = collect($form['visible_fields'] ?? [])->filter(fn ($field) => ($field['field_type'] ?? null) === \App\Models\FormField::TYPE_FILE)->values()->all();
@endphp

<div class="dgcpt-surface p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="dgcpt-card-title">Runtime dynamique</p>
            <h2 class="text-2xl font-bold text-[#E6EEF8]">{{ data_get($form, 'snapshot.template.name', $stage->name) }}</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">{{ data_get($form, 'snapshot.template.description', $stage->description ?: 'Saisie dynamique de l’étape workflow.') }}</p>
        </div>
        <div class="text-right text-xs text-[#9FB3C8]">
            <p><span class="font-semibold text-[#E6EEF8]">Stage :</span> {{ $stage->name }}</p>
            <p><span class="font-semibold text-[#E6EEF8]">Component key :</span> <span class="font-mono">{{ $stage->resolvedComponentKey() }}</span></p>
            <p><span class="font-semibold text-[#E6EEF8]">Dernier statut :</span> {{ $submissionStatus ?: '—' }}</p>
        </div>
    </div>

    <div class="mt-6">
        @include('forms.runtime.wizard', [
            'wizardData' => $wizardData,
            'autosaveData' => $autosaveData,
            'validationSummary' => $validationSummary,
            'attachmentFields' => $attachmentFields,
            'form' => $form,
            'stage' => $stage,
            'instance' => $instance,
            'entretien' => $entretien,
        ])
    </div>
</div>
