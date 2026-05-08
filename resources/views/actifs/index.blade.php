<x-app-layout>

<h2>Processus : {{ $processus->nom }}</h2>

<h3>Actifs</h3>

<form method="POST" action="/actifs">

@csrf

<input type="hidden" name="processus_id" value="{{ $processus->id }}">

<label>Nom de l'actif</label>
<input type="text" name="nom">

<br>

<label>Type</label>

<select name="type">

<option value="essentiel">Actif essentiel</option>

<option value="support">Actif support</option>

</select>

<br>

<label>Description</label>
<textarea name="description"></textarea>

<br><br>

<button type="submit">Ajouter actif</button>

</form>

<br>

<table border="1" cellpadding="10">

<tr>
<th>Actif</th>
<th>Type</th>
<table border="1" cellpadding="10">

<tr>
<th>Actif</th>
<th>Type</th>
<th>Description</th>
<th>Actions</th>
</tr>

@foreach($actifs as $a)

<tr>

<td>{{ $a->nom }}</td>
<td>{{ $a->type }}</td>
<td>{{ $a->description }}</td>

<td>

<a href="/actifs/{{ $a->id }}/risques">⚠️ Risques</a>

</td>

</tr>

@endforeach

</table>
</tr>

@foreach($actifs as $a)

<tr>

<td>{{ $a->nom }}</td>

<td>{{ $a->type }}</td>

<td>{{ $a->description }}</td>

</tr>

@endforeach

</table>

</x-app-layout>
