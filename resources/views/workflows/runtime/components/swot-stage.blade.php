<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-surface p-8 shadow-sm">
            <p class="dgcpt-card-title">Workflow Runtime</p>
            <h1 class="mt-2 text-3xl font-bold text-[#E6EEF8]">{{ $stage->name }}</h1>
            <p class="mt-3 text-sm text-[#9FB3C8]">Execution SWOT integree au runtime visuel du workflow.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                @foreach (($swotView['kpis'] ?? []) as $label => $value)
                    <div class="rounded-2xl bg-[rgba(255,255,255,0.03)] p-4">
                        <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                        <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('workflow-runtime.stage.submit', ['mission' => $instance->mission_id, 'stage' => $stage]) }}" class="mt-6 grid gap-4 md:grid-cols-[1fr,1fr,auto]">
                @csrf
                @if ($stage->resolvedStageType()?->value === 'swot_analysis')
                    <div>
                        <label class="dgcpt-label">Template SWOT</label>
                        <input type="hidden" name="swot_template_id" value="{{ $selectedTemplate?->id }}" />
                        <input type="text" value="{{ $selectedTemplate?->name ?? 'Template non configure' }}" disabled class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Notes</label>
                        <input name="notes" type="text" class="dgcpt-input" placeholder="Observations de synthese" />
                    </div>
                @else
                    <div class="md:col-span-2">
                        <label class="dgcpt-label">Validation</label>
                        <input type="text" value="La validation SWOT confirmera l'analyse precedente." disabled class="dgcpt-input" />
                    </div>
                @endif
                <div class="flex items-end">
                    <button type="submit" class="dgcpt-btn-primary">
                        {{ $stage->resolvedStageType()?->value === 'swot_analysis' ? 'Executer analyse' : 'Valider SWOT' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
