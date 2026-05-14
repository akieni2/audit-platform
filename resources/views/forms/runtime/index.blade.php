@php
    $submissionStatus = $form['current_submission']?->status;
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

    <form method="POST"
          action="{{ route('workflow-runtime.stage.submit', ['mission' => $instance->mission_id, 'stage' => $stage]) }}"
          class="mt-6 space-y-5"
          enctype="multipart/form-data">
        @csrf

        @if ($entretien)
            <input type="hidden" name="entretien_id" value="{{ $entretien->id }}" />
        @endif

        @foreach ($form['visible_fields'] as $field)
            @php
                $value = $form['values'][$field['field_key']] ?? $field['default_value'] ?? null;
                $partial = match ($field['field_type']) {
                    \App\Models\FormField::TYPE_TEXT,
                    \App\Models\FormField::TYPE_NUMBER,
                    \App\Models\FormField::TYPE_DATE,
                    \App\Models\FormField::TYPE_DATETIME => 'forms.runtime.fields.input',
                    \App\Models\FormField::TYPE_TEXTAREA => 'forms.runtime.fields.textarea',
                    \App\Models\FormField::TYPE_BOOLEAN => 'forms.runtime.fields.boolean',
                    \App\Models\FormField::TYPE_FILE => 'forms.runtime.fields.file',
                    \App\Models\FormField::TYPE_SELECT,
                    \App\Models\FormField::TYPE_MULTISELECT,
                    \App\Models\FormField::TYPE_CHECKBOX,
                    \App\Models\FormField::TYPE_RADIO => 'forms.runtime.fields.choice',
                    default => 'forms.runtime.fields.selector',
                };
            @endphp

            @include($partial, ['field' => $field, 'value' => $value, 'runtimeOptions' => $field['runtime_options'] ?? []])
        @endforeach

        <div class="flex flex-wrap gap-3 border-t border-[rgba(0,209,255,0.12)] pt-4">
            <button type="submit" name="action" value="save" class="dgcpt-btn-outline">Enregistrer brouillon</button>
            <button type="submit" name="action" value="complete" class="dgcpt-btn-primary">Valider l’étape</button>
        </div>
    </form>
</div>
