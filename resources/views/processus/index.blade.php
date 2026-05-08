<x-app-layout>

<h2>Mission : {{ $mission->organisation }}</h2>

<h3>Processus</h3>

<form method="POST" action="/processus">

@csrf

<input type="hidden" name="mission_id" value="{{ $mission->id }}">

<label>Nom du processus</label>
<input type="text" name="nom">

<br>

<label>Description</label>
<textarea name="description"></textarea>

<br><br>

<button type="submit">Ajouter processus</button>

</form>

<br>

<table border="1" cellpadding="10">

<tr>
<th>Processus</th>
<th>Description</th>
<th>Actions</th>
</tr>

@foreach($processus as $p)

<tr>

<td>{{ $p->nom }}</td>

<td>{{ $p->description }}</td>

<td>

<a href="/processus/{{ $p->id }}/actifs">📦 Actifs</a>

</td>

</tr>

@endforeach

</table>

</x-app-layout>
