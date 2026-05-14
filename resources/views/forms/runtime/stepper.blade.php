<div class="rounded-3xl border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4">
    <div class="flex flex-wrap items-center gap-3">
        @foreach ($wizardData['steps'] as $step)
            <button type="button"
                    class="form-step-trigger inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition"
                    data-step-trigger="{{ $step['index'] }}"
                    @if ($loop->first) data-active="true" @endif>
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(0,209,255,0.12)] text-[#73D8FF]">{{ $step['index'] + 1 }}</span>
                <span>{{ $step['title'] }}</span>
                <span class="text-[#9FB3C8]">{{ count($step['field_keys']) }}</span>
            </button>
        @endforeach
    </div>
</div>
