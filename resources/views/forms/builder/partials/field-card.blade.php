@php
    $isSelected = isset($selectedField) && $selectedField?->is($field);
    $optionsPreview = $field->options->pluck('label')->take(3)->implode(', ');
@endphp

<div class="rounded-2xl border p-4 {{ $isSelected ? 'border-[rgba(115,216,255,0.28)] bg-[rgba(12,32,58,0.72)]' : 'border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)]' }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#E6EEF8]">{{ $field->label }}</p>
            <p class="mt-1 text-[11px] font-mono uppercase tracking-wide text-[#7E92A7]">{{ $field->field_key }}</p>
        </div>
        <span class="rounded-full bg-[#17223B] px-2.5 py-1 text-[11px] font-semibold text-[#BFD2E6]">
            {{ \App\Models\FormField::fieldTypeLabels()[$field->field_type] ?? $field->field_type }}
        </span>
    </div>

    <div class="mt-3 space-y-2 text-xs text-[#9FB3C8]">
        <p><span class="font-semibold text-[#E6EEF8]">Ordre :</span> {{ $field->sort_order }}</p>
        <p><span class="font-semibold text-[#E6EEF8]">Requis :</span> {{ $field->is_required ? 'Oui' : 'Non' }}</p>
        @if ($field->usesOptions() && $optionsPreview !== '')
            <p><span class="font-semibold text-[#E6EEF8]">Options :</span> {{ $optionsPreview }}</p>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('form-builder.edit', ['template' => $template, 'field' => $field->id]) }}"
           class="rounded-lg border border-[rgba(0,209,255,0.18)] px-3 py-1.5 text-xs font-semibold text-[#73D8FF] hover:bg-[rgba(0,209,255,0.08)]">
            Configurer
        </a>
        @if ($isSelected)
            <span class="rounded-lg bg-[rgba(0,168,107,0.12)] px-3 py-1.5 text-xs font-semibold text-[#7EF2BE]">Sélectionné</span>
        @endif
    </div>
</div>
