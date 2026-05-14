@php
    $multiple = (bool) data_get($field, 'configuration.multiple', in_array($field['field_type'], [\App\Models\FormField::TYPE_RISK_SELECTOR], true));
    $selectedValues = collect(old($field['field_key'], $value));
    if (! is_array($selectedValues->all())) {
        $selectedValues = collect([$selectedValues->first()]);
    }
@endphp

<div class="space-y-2">
    <label class="dgcpt-label" for="field-{{ $field['field_key'] }}">
        {{ $field['label'] }}
        @if ($field['is_required'])
            <span class="text-[#FF9B9B]">*</span>
        @endif
    </label>
    <select id="field-{{ $field['field_key'] }}"
            name="{{ $field['field_key'] }}{{ $multiple ? '[]' : '' }}"
            @if ($multiple) multiple @endif
            class="dgcpt-input min-h-[2.8rem]">
        @if (! $multiple)
            <option value="">Choisir…</option>
        @endif
        @foreach ($runtimeOptions as $option)
            <option value="{{ $option['value'] }}"
                    @selected($selectedValues->contains((string) $option['value']) || $selectedValues->contains($option['value']))>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>
    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
