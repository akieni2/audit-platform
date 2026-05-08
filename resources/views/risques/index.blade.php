<x-app-layout>

<h2>Actif : {{ $actif->nom }}</h2>

<h3>Risques</h3>

<!-- LEGENDE DES RISQUES -->

<h4>Légende d’évaluation</h4>

<table border="1" cellpadding="8">

<tr>
<th>Score</th>
<th>Niveau</th>
<th>Priorité</th>
</tr>

<tr>
<td>1 - 4</td>
<td style="color:green;">Faible</td>
<td>Surveillance</td>
</tr>

<tr>
<td>5 - 9</td>
<td style="color:orange;">Modéré</td>
<td>Plan d'action</td>
</tr>

<tr>
<td>10 - 15</td>
<td style="color:red;">Elevé</td>
<td>Correction rapide</td>
</tr>

<tr>
<td>16 - 25</td>
<td style="color:darkred;">Critique</td>
<td>Action immédiate</td>
</tr>

</table>

<br>

<!-- FORMULAIRE AJOUT RISQUE -->

<form method="POST" action="/risques">

@csrf

<input type="hidden" name="actif_id" value="{{ $actif->id }}">

<label>Description du risque</label><br>
<input type="text" name="description" style="width:300px" required>

<br><br>

<label>Impact (1-5)</label><br>
<input type="number" name="impact_inherent" min="1" max="5" required>

<br><br>

<label>Probabilité (1-5)</label><br>
<input type="number" name="probabilite_inherent" min="1" max="5" required>

<br><br>

<button type="submit">Ajouter risque</button>

</form>

<br><br>

<!-- TABLEAU DES RISQUES -->

<table border="1" cellpadding="8">

<tr>
<th>Risque</th>
<th>Impact</th>
<th>Probabilité</th>
<th>Score inhérent</th>
<th>Niveau inhérent</th>
<th>Score résiduel</th>
<th>Niveau résiduel</th>
<th>Actions</th>
</tr>

@forelse($risques as $r)

<tr>

<td>{{ $r->description }}</td>

<td>{{ $r->impact_inherent }}</td>

<td>{{ $r->probabilite_inherent }}</td>

<td>{{ $r->score_inherent }}</td>

<td>

@if($r->score_inherent <= 4)
<span style="color:green;">Faible</span>

@elseif($r->score_inherent <= 9)
<span style="color:orange;">Modéré</span>

@elseif($r->score_inherent <= 15)
<span style="color:red;">Elevé</span>

@else
<span style="color:darkred;">Critique</span>
@endif

</td>

<td>{{ $r->score_residuel ?? '-' }}</td>

<td>

@if(($r->score_residuel ?? 0) <= 4)
<span style="color:green;">Faible</span>

@elseif(($r->score_residuel ?? 0) <= 9)
<span style="color:orange;">Modéré</span>

@elseif(($r->score_residuel ?? 0) <= 15)
<span style="color:red;">Elevé</span>

@elseif(($r->score_residuel ?? 0) > 15)
<span style="color:darkred;">Critique</span>

@else
-
@endif

</td>

<td>

<a href="/risques/{{ $r->id }}/constats">
?? Constats
</a>

<br>

<a href="/risques/{{ $r->id }}/actions">
?? Plan d'action
</a>

</td>

</tr>

@empty

<tr>
<td colspan="8" style="text-align:center;">
Aucun risque enregistré pour cet actif
</td>
</tr>

@endforelse

</table>

</x-app-layout>