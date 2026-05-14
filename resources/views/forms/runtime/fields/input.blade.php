@php
    $type = match ($field['field_type']) {
        \App\Models\FormField::TYPE_NUMBER => 'number',
        \App\Models\FormField::TYPE_DATE => 'date',
        \App\Models\FormField::TYPE_DATETIME => 'datetime-local',
        default => 'text',
    };
@endphp

<div class="space-y-2">
    <label class="dgcpt-label" for="field-{{ $field['field_key'] }}">
        {{ $field['label'] }}
        @if ($field['is_required'])
            <span class="text-[#FF9B9B]">*</span>
        @endif
    </label>
    <input id="field-{{ $field['field_key'] }}"
           name="{{ $field['field_key'] }}"
           type="{{ $type }}"
           value="{{ old($field['field_key'], is_array($value) ? json_encode($value) : $value) }}"
           placeholder="{{ $field['placeholder'] }}"
           class="dgcpt-input" />
    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
