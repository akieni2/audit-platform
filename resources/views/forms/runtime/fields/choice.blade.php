@php
    $fieldType = $field['field_type'];
    $selectedValues = collect(old($field['field_key'], $value));
    if (! is_array($selectedValues->all())) {
        $selectedValues = collect([$selectedValues->first()]);
    }
@endphp

<div class="space-y-2">
    <label class="dgcpt-label">{{ $field['label'] }}</label>

    @if (in_array($fieldType, [\App\Models\FormField::TYPE_SELECT, \App\Models\FormField::TYPE_MULTISELECT], true))
        <select name="{{ $field['field_key'] }}{{ $fieldType === \App\Models\FormField::TYPE_MULTISELECT ? '[]' : '' }}"
                @if ($fieldType === \App\Models\FormField::TYPE_MULTISELECT) multiple @endif
                class="dgcpt-input min-h-[2.8rem]">
            @if ($fieldType === \App\Models\FormField::TYPE_SELECT)
                <option value="">Choisir…</option>
            @endif
            @foreach ($runtimeOptions as $option)
                <option value="{{ $option['value'] }}"
                        @selected($selectedValues->contains((string) $option['value']) || $selectedValues->contains($option['value']))>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    @else
        <div class="grid gap-2">
            @foreach ($runtimeOptions as $option)
                <label class="inline-flex items-center gap-3 rounded-2xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] px-4 py-3 text-sm text-[#E6EEF8]">
                    <input name="{{ $field['field_key'] }}{{ $fieldType === \App\Models\FormField::TYPE_CHECKBOX ? '[]' : '' }}"
                           type="{{ $fieldType === \App\Models\FormField::TYPE_CHECKBOX ? 'checkbox' : 'radio' }}"
                           value="{{ $option['value'] }}"
                           @checked($selectedValues->contains((string) $option['value']) || $selectedValues->contains($option['value']))
                           class="rounded border-[rgba(0,209,255,0.22)] bg-[#050816]" />
                    <span>{{ $option['label'] }}</span>
                </label>
            @endforeach
        </div>
    @endif

    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
