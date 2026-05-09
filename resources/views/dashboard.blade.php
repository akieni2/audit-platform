<x-app-layout>

<h2>Tableau de bord audit</h2>

@if(isset($departments) && $departments->isNotEmpty())
<p style="color:#475569;font-size:14px;margin-bottom:8px;">
    Votre rattachement :
    @if(auth()->user()?->department)
        <strong>{{ auth()->user()->department->code }}</strong> — {{ auth()->user()->department->name }}
    @else
        <em>non défini</em>
    @endif
</p>

@if(!empty($focusedDepartment) && auth()->user()?->canViewAllInstitutionalData())
<div style="background:#dbeafe;border:1px solid #3b82f6;color:#1e3a8a;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px;">
    <strong>Vue pôle :</strong> {{ $focusedDepartment->code }} — {{ $focusedDepartment->name }}.
    Les indicateurs ci-dessous sont <em>limités à ce département</em> (comme pour un auditeur du pôle).
    <a href="{{ route('dashboard', ['department' => 'all']) }}" style="margin-left:10px;color:#1d4ed8;font-weight:600;">Revenir à la vue globale</a>
</div>
@endif

<p style="color:#475569;font-size:13px;margin-bottom:10px;">
    @if(auth()->user()?->canViewAllInstitutionalData())
        <strong>Choisir un pôle :</strong> cliquez pour appliquer le filtre sur <em>ce</em> tableau de bord (missions, risques, actions). Les liens du menu restent globaux ; pour lister les missions du pôle, utilisez aussi la section « Pôles / départements ».
    @else
        Raccourci : ouvrez le tableau de bord filtré sur votre pôle (équivalent à la navigation missions).
    @endif
</p>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    @foreach($departments as $dept)
        @php $isFocus = isset($dashboardDepartmentFocusId) && (int) $dashboardDepartmentFocusId === (int) $dept->id; @endphp
        <a href="{{ route('dashboard', ['department' => $dept->id]) }}"
           style="display:inline-block;padding:8px 12px;border-radius:6px;text-decoration:none;font-size:13px;
           {{ $isFocus ? 'background:#2563eb;color:white;font-weight:bold;' : 'background:#e2e8f0;color:#0f172a;' }}">
            <strong>{{ $dept->code }}</strong>
            <span style="{{ $isFocus ? 'color:#e0e7ff;' : 'color:#475569;' }}">{{ \Illuminate\Support\Str::limit($dept->name, 40) }}</span>
        </a>
    @endforeach
    @if(auth()->user()?->canViewAllInstitutionalData())
        <a href="{{ route('dashboard', ['department' => 'all']) }}"
           style="display:inline-block;background:#f1f5f9;color:#64748b;padding:8px 12px;border-radius:6px;text-decoration:none;font-size:13px;border:1px solid #cbd5e1;">
            Vue globale
        </a>
    @endif
</div>
@endif

<br>

<div style="display:flex;gap:20px;flex-wrap:wrap;">

<div style="background:#1e293b;color:white;padding:20px;width:200px;">
<h3>Missions</h3>
<h1>{{ $missions }}</h1>
</div>

<div style="background:#2563eb;color:white;padding:20px;width:200px;">
<h3>Risques</h3>
<h1>{{ $risques }}</h1>
</div>

<div style="background:#b91c1c;color:white;padding:20px;width:200px;">
<h3>Risques critiques</h3>
<h1>{{ $risquesCritiques }}</h1>
</div>

<div style="background:#f97316;color:white;padding:20px;width:200px;">
<h3>Actions ouvertes</h3>
<h1>{{ $actionsOuvertes }}</h1>
</div>

<div style="background:black;color:white;padding:20px;width:200px;">
<h3>Actions en retard</h3>
<h1>{{ $actionsRetard }}</h1>
</div>

</div>

<br><br>

<h3>Répartition des risques par service</h3>

<canvas id="riskChart" height="100"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('riskChart');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels: [

            @foreach($services ?? [] as $service)

                "{{ $service->nom }}",

            @endforeach

        ],

        datasets: [{

            label: 'Nombre de risques',

            data: [

                @foreach($services ?? [] as $service)

                    {{ $service->risques_count ?? 0 }},

                @endforeach

            ],

            backgroundColor: '#2563eb'

        }]

    },

    options: {

        responsive: true,

        scales: {

            y: {

                beginAtZero: true

            }

        }

    }

});

</script>

<br><br>

@if($risquesCritiques > 0)

<div style="background:#b91c1c;color:white;padding:15px;">
? Alerte : Risques critiques détectés
</div>

@else

<div style="background:green;color:white;padding:15px;">
? Situation maîtrisée
</div>

@endif

</x-app-layout>