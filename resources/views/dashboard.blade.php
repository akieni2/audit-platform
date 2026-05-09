<x-app-layout>

<h2>Dashboard Intelligent</h2>

@if(isset($departments) && $departments->isNotEmpty())
<p style="color:#475569;font-size:14px;margin-bottom:12px;">
    Pôles et départements actifs (données base). Votre rattachement :
    @if(auth()->user()?->department)
        <strong>{{ auth()->user()->department->code }}</strong> — {{ auth()->user()->department->name }}
    @else
        <em>non défini</em>
    @endif
</p>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    @foreach($departments as $dept)
        <a href="{{ route('missions.index', ['department' => $dept->id]) }}"
           style="display:inline-block;background:#e2e8f0;color:#0f172a;padding:8px 12px;border-radius:6px;text-decoration:none;font-size:13px;">
            <strong>{{ $dept->code }}</strong>
            <span style="color:#475569;">{{ \Illuminate\Support\Str::limit($dept->name, 40) }}</span>
        </a>
    @endforeach
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