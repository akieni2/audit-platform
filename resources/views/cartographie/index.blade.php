<x-app-layout>

<div style="max-width:1200px;margin:0 auto;">

<h2 style="margin-bottom:8px;">Cartographie des risques</h2>
<p style="color:#475569;margin-bottom:24px;">
    Mission : <strong>{{ $mission->organisation }}</strong>
</p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;">

    <div style="background:white;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="font-size:13px;color:#64748b;">Risques critiques</div>
        <div style="font-size:32px;font-weight:700;color:#b91c1c;">{{ $dashboard['critical_count'] }}</div>
        <div style="font-size:12px;color:#94a3b8;">Inherent ou residuel = Critique</div>
    </div>

    <div style="background:white;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);grid-column:span 2;">
        <div style="font-size:14px;font-weight:600;margin-bottom:12px;">Top risques (score inherent)</div>
        <ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.6;">
            @forelse($dashboard['top_risks'] as $tr)
                <li>
                    <strong>{{ \Illuminate\Support\Str::limit($tr->description, 60) }}</strong>
                    &mdash; score {{ $tr->score_inherent }}
                    @if($tr->departement)
                        <span style="color:#64748b;">({{ $tr->departement }})</span>
                    @endif
                </li>
            @empty
                <li style="color:#64748b;">Aucun risque pour cette mission.</li>
            @endforelse
        </ul>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;">
    <div style="background:white;padding:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="font-size:14px;font-weight:600;margin-bottom:8px;">Evolution mensuelle (creations)</div>
        <canvas id="chartMonthly" height="160"></canvas>
    </div>
    <div style="background:white;padding:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="font-size:14px;font-weight:600;margin-bottom:8px;">Risques par departement</div>
        <canvas id="chartDept" height="160"></canvas>
    </div>
</div>

<div style="background:white;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:28px;">
    <h3 style="margin-top:0;">Heatmap (Impact x Probabilite)</h3>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px;">
        Axe horizontal : impact (1 a 5). Axe vertical : probabilite (5 a 1). Couleur = criticite theorique de la cellule (vert, jaune, orange, rouge).
    </p>

    <div style="overflow-x:auto;">
        <table style="border-collapse:separate;border-spacing:4px;margin:0 auto;">
            <thead>
                <tr>
                    <th style="padding:8px;font-size:12px;color:#64748b;"></th>
                    @for ($i = 1; $i <= 5; $i++)
                        <th style="padding:8px;font-size:12px;font-weight:600;">Impact {{ $i }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach($heatmapRows as $row)
                    <tr>
                        <th style="padding:8px;font-size:12px;color:#64748b;white-space:nowrap;">
                            Probabilite {{ $row[0]['probabilite'] }}
                        </th>
                        @foreach($row as $cell)
                            <td style="text-align:center;border-radius:6px;padding:12px 10px;font-weight:600;font-size:13px;"
                                class="{{ $cell['cell_classes'] }}"
                                title="Score cellule {{ $cell['score'] }} — {{ $cell['level']->label() }}">
                                {{ $cell['count'] }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.tailwindcss.com"></script>

<div style="background:white;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
    <h3 style="margin-top:0;">Detail des risques</h3>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#1e293b;color:white;">
                    <th style="padding:8px;text-align:left;">Description</th>
                    <th style="padding:8px;">IxP</th>
                    <th style="padding:8px;">Crit. inh.</th>
                    <th style="padding:8px;">Score res.</th>
                    <th style="padding:8px;">Crit. res.</th>
                    <th style="padding:8px;">Proprietaire</th>
                    <th style="padding:8px;">Departement</th>
                    <th style="padding:8px;">Statut</th>
                    <th style="padding:8px;">Revue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($risques as $r)
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:8px;">{{ \Illuminate\Support\Str::limit($r->description, 48) }}</td>
                        <td style="padding:8px;text-align:center;">{{ $r->impact_inherent }}x{{ $r->probabilite_inherent }}</td>
                        <td style="padding:8px;text-align:center;">
                            {{ \App\Domain\Risk\Enums\CriticalityLevel::tryFrom($r->criticite_inherent ?? '')?->label() ?? '—' }}
                        </td>
                        <td style="padding:8px;text-align:center;">{{ $r->score_residuel ?? '—' }}</td>
                        <td style="padding:8px;text-align:center;">
                            {{ \App\Domain\Risk\Enums\CriticalityLevel::tryFrom($r->criticite_residuel ?? '')?->label() ?? '—' }}
                        </td>
                        <td style="padding:8px;">{{ $r->proprietaire ?? '—' }}</td>
                        <td style="padding:8px;">{{ $r->departement ?? '—' }}</td>
                        <td style="padding:8px;">
                            {{ \App\Domain\Risk\Enums\RiskStatus::tryFrom($r->statut_risque ?? '')?->label() ?? ($r->statut_risque ?? '—') }}
                        </td>
                        <td style="padding:8px;">{{ $r->date_revue?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:16px;text-align:center;color:#64748b;">Aucun risque.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>

@php
    $monthly = collect($dashboard['monthly'])->sortKeys();
    $dept = $dashboard['by_department'];
@endphp

<script>
(function(){
    const monthlyLabels = @json($monthly->keys()->values());
    const monthlyData = @json($monthly->values());
    const deptLabels = @json(array_keys($dept));
    const deptData = @json(array_values($dept));

    if (typeof Chart !== 'undefined') {
        new Chart(document.getElementById('chartMonthly'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Risques crees',
                    data: monthlyData,
                    backgroundColor: 'rgba(37, 99, 235, 0.6)',
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        new Chart(document.getElementById('chartDept'), {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptData,
                    backgroundColor: ['#22c55e','#eab308','#f97316','#ef4444','#6366f1','#94a3b8'],
                }]
            },
            options: { responsive: true }
        });
    }
})();
</script>

</x-app-layout>
