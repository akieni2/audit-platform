<div class="space-y-2">
    <label class="dgcpt-label">{{ $field['label'] }}</label>
    <label class="inline-flex items-center gap-3 rounded-2xl border border-[rgba(0,209,255,0.12)] bg-[rgba(5,8,22,0.72)] px-4 py-3 text-sm text-[#E6EEF8]">
        <input type="hidden" name="{{ $field['field_key'] }}" value="0" />
        <input id="field-{{ $field['field_key'] }}"
               name="{{ $field['field_key'] }}"
               type="checkbox"
               value="1"
               @checked((bool) old($field['field_key'], $value))
               class="rounded border-[rgba(0,209,255,0.22)] bg-[#050816]" />
        <span>{{ $field['placeholder'] ?: 'Oui / non' }}</span>
    </label>
    @if ($field['help_text'])
        <p class="text-xs text-[#9FB3C8]">{{ $field['help_text'] }}</p>
    @endif
</div>
