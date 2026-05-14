<div class="space-y-2">
    <label class="dgcpt-label" for="field-{{ $field['field_key'] }}">
        {{ $field['label'] }}
        @if ($field['is_required'])
            <span class="text-[#FF9B9B]">*</span>
        @endif
    </label>
    <input id="field-{{ $field['field_key'] }}"
           name="{{ $field['field_key'] }}{{ $field['is_repeatable'] ? '[]' : '' }}"
           type="file"
           @if ($field['is_repeatable']) multiple @endif
           class="block w-full rounded-2xl border border-[rgba(0,209,255,0.22)] bg-[#050816] px-4 py-3 text-sm text-[#E6EEF8]" />
    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
