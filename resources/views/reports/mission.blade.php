<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size:12px; }
h1 { text-align:center; }
table { width:100%; border-collapse:collapse; margin-bottom:20px; }
th, td { border:1px solid #000; padding:6px; }
th { background:#f2f2f2; }
</style>
</head>
<body>

<h1>RAPPORT D'AUDIT</h1>

<h3>Mission : {{ $mission->organisation }}</h3>
<p>Description : {{ $mission->description }}</p>
<p>Période : {{ $mission->date_debut }} au {{ $mission->date_fin }}</p>

<hr>

<h2>Risques identifiés</h2>

<table>
<tr>
<th>Description</th>
<th>Score inhérent</th>
<th>Score résiduel</th>
</tr>

@foreach($mission->services as $service)
@foreach($service->processus as $processus)
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

@foreach($mission->services as $service)
@foreach($service->processus as $processus)
@foreach($processus->actifs as $actif)
@foreach($actif->risques as $risque)
@foreach($risque->actionsCorrectives as $action)

<tr>
<td>{{ $risque->description }}</td>
<td>{{ $action->description }}</td>
<td>{{ $action->responsable }}</td>
<td>{{ $action->statut }}</td>
</tr>

@endforeach
@endforeach
@endforeach
@endforeach
@endforeach

</table>

<br><br>

<p>Date de génération : {{ now() }}</p>

</body>
</html>
