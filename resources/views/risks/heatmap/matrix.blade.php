<div class="dgcpt-surface p-6 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="dgcpt-card-title">Visual Heatmap Engine</p>
            <h2 class="text-xl font-bold text-[#E6EEF8]">Matrice 5x5 interactive</h2>
            <p class="mt-1 text-sm text-[#9FB3C8]">Impact x probabilité, densité et lecture mission/département/nationale.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">Inhérent</span>
            <span class="rounded-full bg-[rgba(255,255,255,0.05)] px-3 py-1 text-xs font-semibold text-[#BFD2E6]">Résiduel</span>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @foreach (['inherent' => 'Inhérente', 'residual' => 'Résiduelle'] as $mode => $label)
            @php
                $matrix = $heatmapView['modes'][$mode];
            @endphp
            <div class="rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-bold text-[#E6EEF8]">{{ $label }}</h3>
                    <span class="text-xs text-[#9FB3C8]">{{ $matrix['totals']['count'] }} risques</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="mx-auto border-separate border-spacing-2">
                        <thead>
                            <tr>
                                <th class="px-2 py-1 text-xs text-[#9FB3C8]"></th>
                                @for ($impact = 1; $impact <= 5; $impact++)
                                    <th class="px-3 py-2 text-xs font-semibold text-[#BFD2E6]">Impact {{ $impact }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matrix['rows'] as $row)
                                <tr>
                                    <th class="px-2 py-2 text-xs text-[#9FB3C8]">Prob. {{ $row[0]['probabilite'] }}</th>
                                    @foreach ($row as $cell)
                                        <td class="min-w-16 rounded-2xl border border-[rgba(255,255,255,0.04)] px-3 py-4 text-center"
                                            style="background: color-mix(in srgb, {{ $cell['color_token'] }} 24%, #050816);">
                                            <p class="text-lg font-bold text-[#E6EEF8]">{{ $cell['display_count'] }}</p>
                                            <p class="mt-1 text-[10px] uppercase tracking-[0.18em] text-[#BFD2E6]">{{ $cell['score'] }}</p>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
