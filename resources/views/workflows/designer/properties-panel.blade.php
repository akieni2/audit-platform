<div class="dgcpt-surface space-y-4 p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="dgcpt-card-title">Properties panel</p>
            <h2 class="text-lg font-bold text-[#E6EEF8]">Stage & transitions</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">Configuration rapide, validation visuelle et badges runtime.</p>
        </div>
        @if ($selectedStage)
            <span class="rounded-full bg-[rgba(0,168,107,0.12)] px-3 py-1 text-xs font-semibold text-[#7EF2BE]">
                {{ $selectedStage->name }}
            </span>
        @endif
    </div>

    @if ($selectedStage)
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-[rgba(0,209,255,0.08)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[#73D8FF]">Inline edition</p>
                <p class="mt-2 text-sm text-[#E6EEF8]">{{ $selectedStage->resolvedComponentKey() }}</p>
                <p class="mt-1 text-xs text-[#9FB3C8]">Lane: {{ collect($canvas['nodes'])->firstWhere('id', $selectedStage->id)['lane'] ?? 'Flow principal' }}</p>
            </div>
            <div class="rounded-2xl border border-[rgba(0,209,255,0.08)] bg-[rgba(5,8,22,0.72)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[#73D8FF]">Visual validation</p>
                <p class="mt-2 text-sm text-[#E6EEF8]">
                    {{ collect($canvas['transitions'])->where('from_stage_id', $selectedStage->id)->count() }} transitions sortantes
                </p>
                <p class="mt-1 text-xs text-[#9FB3C8]">
                    {{ collect($canvas['transitions'])->where('to_stage_id', $selectedStage->id)->count() }} transitions entrantes
                </p>
            </div>
        </div>

        @php
            $relatedTransitions = collect($canvas['transitions'])->filter(fn ($edge) => $edge['from_stage_id'] === $selectedStage->id || $edge['to_stage_id'] === $selectedStage->id);
        @endphp

        <div class="space-y-2">
            @forelse ($relatedTransitions as $edge)
                <div class="rounded-2xl border px-4 py-3 text-sm {{ $edge['is_valid'] ? 'border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] text-[#BFD2E6]' : 'border-[rgba(255,90,90,0.20)] bg-[rgba(58,26,32,0.45)] text-[#FFD4D4]' }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-[#E6EEF8]">{{ $edge['from_label'] }} → {{ $edge['to_label'] }}</span>
                        <span>{{ $edge['is_automatic'] ? 'Auto' : 'Manual' }}</span>
                    </div>
                    @if (! empty($edge['validation_messages']))
                        <ul class="mt-2 list-disc pl-5 text-xs">
                            @foreach ($edge['validation_messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-[rgba(0,209,255,0.12)] px-4 py-4 text-sm text-[#9FB3C8]">
                    Aucune transition liée à cette étape pour le moment.
                </div>
            @endforelse
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-[rgba(0,209,255,0.12)] px-4 py-4 text-sm text-[#9FB3C8]">
            Sélectionnez une étape sur le canvas pour afficher le panneau de propriétés et les transitions liées.
        </div>
    @endif
</div>
