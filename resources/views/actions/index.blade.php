<x-app-layout>

<h2>Risque : {{ $risque->description }}</h2>

<h3>Plan d’actions correctives</h3>

<br>

<!-- FORMULAIRE AJOUT ACTION -->

<form method="POST" action="/risques/{{ $risque->id }}/actions">

@csrf

<input type="hidden" name="risque_id" value="{{ $risque->id }}">

<label>Description de l'action</label><br>
<textarea name="description" style="width:400px;height:80px" required></textarea>

<br><br>

<label>Responsable</label><br>
<input type="text" name="responsable">

<br><br>

<label>Date d'échéance</label><br>
<input type="date" name="date_echeance">

<br><br>

<button type="submit">Ajouter action</button>

</form>

<br><br>

<!-- COMPTEUR GLOBAL -->

@php
$ouvert = $actions->where('statut','ouvert')->count();
$encours = $actions->where('statut','en_cours')->count();
$ferme = $actions->where('statut','ferme')->count();
$retard = $actions->filter(fn($a)=>$a->isOverdue())->count();
@endphp

<div style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">

<span style="background:#ff4d4d;color:white;padding:6px;">
Ouvert: {{ $ouvert }}
</span>

<span style="background:orange;color:white;padding:6px;">
En cours: {{ $encours }}
</span>

<span style="background:green;color:white;padding:6px;">
Fermé: {{ $ferme }}
</span>

<span style="background:black;color:white;padding:6px;">
En retard: {{ $retard }}
</span>

</div>

<!-- TABLEAU DES ACTIONS -->

<table border="1" cellpadding="10">

<tr>
<th>Action</th>
<th>Responsable</th>
<th>Échéance</th>
<th>Statut</th>
<th>Suivi</th>
</tr>

@forelse($actions as $a)

<tr>

<td>{{ $a->description }}</td>

<td>{{ $a->responsable }}</td>

<td>{{ $a->date_echeance }}</td>

<td>

@if($a->statut == 'ouvert')
<span style="background:#ff4d4d;color:white;padding:4px;">Ouvert</span>

@elseif($a->statut == 'en_cours')
<span style="background:orange;color:white;padding:4px;">En cours</span>

@else
<span style="background:green;color:white;padding:4px;">Fermé</span>
@endif

</td>

<td>

@if($a->isOverdue())
<span style="color:red;font-weight:bold;">? En retard</span>
@else
<span style="color:green;">OK</span>
@endif

</td>

</tr>

@empty

<tr>
<td colspan="5" style="text-align:center;">
Aucune action corrective enregistrée
</td>
</tr>

@endforelse

</table>

</x-app-layout>