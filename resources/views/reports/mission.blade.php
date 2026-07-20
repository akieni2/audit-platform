<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
h1 { text-align: center; font-size: 18px; margin-bottom: 6px; letter-spacing: 0.02em; }
h2 { font-size: 13px; margin-top: 18px; margin-bottom: 8px; border-bottom: 1px solid #333; padding-bottom: 4px; }
h3 { font-size: 12px; margin: 10px 0 6px; }
.meta { margin: 8px 0 14px; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 10px; }
th, td { border: 1px solid #444; padding: 5px 6px; vertical-align: top; }
th { background: #e8e8e8; font-weight: 700; text-align: left; }
.small { font-size: 9px; color: #333; }
.header-band { background: #1e293b; color: #fff; padding: 10px 12px; margin: -8px -8px 12px; text-align: center; }
.header-band .sub { font-size: 9px; opacity: 0.9; margin-top: 4px; }
.timeline-row td:first-child { white-space: nowrap; width: 22%; }
</style>
</head>
<body>

<div class="header-band" style="display:flex;align-items:center;justify-content:center;gap:12px;">
    @if (file_exists(public_path('assets/branding/dgcpt-logo.png')))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/branding/dgcpt-logo.png'))) }}" alt="DGCPT" style="height:48px;width:auto;">
    @endif
    <div>
        <div style="font-size: 11px; font-weight: 700;">RÉPUBLIQUE — RAPPORT D'AUDIT INSTITUTIONNEL</div>
        <div class="sub">DGCPT · Trésor public gabonais · Traçabilité workflow · Document confidentiel</div>
    </div>
</div>

<h1>RAPPORT D'AUDIT</h1>

<h3>Mission : {{ $mission->organisation }}</h3>
<div class="meta">
    <p><strong>Description :</strong> {{ $mission->description }}</p>
    <p><strong>Période :</strong> {{ $mission->date_debut }} au {{ $mission->date_fin ?? '—' }}</p>
    <p><strong>État de validation :</strong> {{ \App\Support\UiLabel::translate($mission->mission_status) }}</p>
    @if ($mission->department)
        <p><strong>Pôle / département :</strong> {{ $mission->department->code }} — {{ $mission->department->name }}</p>
    @endif
    @if ($mission->auditeur)
        <p><strong>Auditeur responsable :</strong> {{ $mission->auditeur->displayName() }}</p>
    @endif
</div>

<h2>Historique du workflow</h2>
<table class="timeline-row">
<tr>
    <th>Date</th>
    <th>Action</th>
    <th>Acteur</th>
    <th>Commentaire</th>
</tr>
@forelse ($mission->workflowEvents as $evt)
<tr>
    <td>{{ $evt->created_at?->format('d/m/Y H:i') }}</td>
    <td>{{ $evt->action }} — {{ \App\Support\UiLabel::translate($evt->from_status) }} → {{ \App\Support\UiLabel::translate($evt->to_status) }}</td>
    <td>{{ $evt->user?->displayName() ?? '—' }}</td>
    <td>{{ $evt->comment ?: '—' }}</td>
</tr>
@empty
<tr><td colspan="4">Aucun événement de workflow enregistré.</td></tr>
@endforelse
</table>

<h2>Risques identifiés</h2>

<table>
<tr>
<th>Description</th>
<th>Score inhérent</th>
<th>Score résiduel</th>
</tr>

@foreach($mission->processus as $processus)
@foreach($processus->actifs as $actif)
@foreach($actif->risques as $risque)

<tr>
<td>{{ $risque->description }}</td>
<td>{{ $risque->score_inherent }}</td>
<td>{{ $risque->score_residuel }}</td>
</tr>

@endforeach
@endforeach
@endforeach

</table>

<h2>Actions correctives</h2>

<table>
<tr>
<th>Risque</th>
<th>Action</th>
<th>Responsable</th>
<th>Statut</th>
</tr>

@foreach($mission->processus as $processus)
@foreach($processus->actifs as $actif)
@foreach($actif->risques as $risque)
@foreach($risque->actionsCorrectives as $action)

<tr>
<td>{{ \Illuminate\Support\Str::limit($risque->description, 80) }}</td>
<td>{{ $action->description }}</td>
<td>{{ $action->responsable }}</td>
<td>{{ $action->statut }}</td>
</tr>

@endforeach
@endforeach
@endforeach
@endforeach

</table>

@if ($mission->services->isNotEmpty())
<h2>Services audités (référence)</h2>
<table>
<tr><th>Nom</th><th>Description</th></tr>
@foreach ($mission->services as $service)
<tr>
<td>{{ $service->nom }}</td>
<td>{{ $service->description }}</td>
</tr>
@endforeach
</table>
@endif

<p class="small">Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ config('app.name') }}</p>

</body>
</html>
