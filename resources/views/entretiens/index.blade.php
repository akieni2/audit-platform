<x-app-layout>

<h2>Service : {{ $service->nom }}</h2>

<h3>Entretien d'audit</h3>

<form method="POST" action="/entretiens">

@csrf

<input type="hidden" name="service_id" value="{{ $service->id }}">
<input type="hidden" name="mission_id" value="{{ $service->mission_id }}">

<label>Nom du responsable</label>
<input type="text" name="responsable_nom">

<br>

<label>Rôle / fonction</label>
<input type="text" name="role">

<br>

<label>Chef hiérarchique</label>
<input type="text" name="chef_hierarchique">

<br>

<label>Auditeur</label>
<input type="text" name="auditeur">

<br>

<label>Date entretien</label>
<input type="date" name="date_entretien">

<br>

<label>Notes</label>
<textarea name="notes"></textarea>

<br><br>

<button type="submit">Enregistrer entretien</button>

</form>
<br><br>

<h3>Questionnaire d'entretien</h3>

<table border="1" cellpadding="8">

<tr>
<th>Question</th>
<th>Réponse</th>
<th>Observation</th>
</tr>

@foreach($questions as $q)

<tr>

<td>{{ $q->question }}</td>

<td>
<input type="text" name="reponse[{{ $q->id }}]">
</td>

<td>
<textarea name="observation[{{ $q->id }}]"></textarea>
</td>

</tr>

@endforeach

</table>
<br>

<table border="1">

<tr>
<th>Responsable</th>
<th>Rôle</th>
<th>Chef hiérarchique</th>
<th>Date</th>
</tr>

@foreach($entretiens as $e)

<tr>

<td>{{ $e->responsable_nom }}</td>

<td>{{ $e->role }}</td>

<td>{{ $e->chef_hierarchique }}</td>

<td>{{ $e->date_entretien }}</td>

</tr>

@endforeach

</table>

</x-app-layout>
