<div class="space-y-2">
    <label class="dgcpt-label" for="field-{{ $field['field_key'] }}">
        {{ $field['label'] }}
        @if ($field['is_required'])
            <span class="text-[#FF9B9B]">*</span>
        @endif
    </label>
    <textarea id="field-{{ $field['field_key'] }}"
              name="{{ $field['field_key'] }}"
              rows="4"
              placeholder="{{ $field['placeholder'] }}"
              class="dgcpt-textarea">{{ old($field['field_key'], is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value) }}</textarea>
    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
