<x-app-layout>

<h2>Mission : {{ $mission->organisation }}</h2>

<h3>Services audités</h3>

<form method="POST" action="/services">

@csrf

<input type="hidden" name="mission_id" value="{{ $mission->id }}">

<label>Nom du service</label>
<input type="text" name="nom">

<br><br>

<label>Responsable</label>
<input type="text" name="responsable">

<br><br>

<label>Description</label>
<textarea name="description"></textarea>

<br><br>

<button type="submit">Ajouter service</button>

</form>

<br>

<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%;background:white;">

<tr style="background:#1e293b;color:white;">
<th>Service</th>
<th>Responsable</th>
<th>Description</th>
<th>Entretien</th>
<th>Processus</th>
</tr>

@foreach($services as $s)

<tr>

<td>{{ $s->nom }}</td>

<td>{{ $s->responsable }}</td>

<td>{{ $s->description }}</td>

<td>
<a href="/services/{{ $s->id }}/entretiens">
Entretien
</a>
</td>

<td>
<a href="/missions/{{ $mission->id }}/processus">
Processus
</a>
</td>

</tr>

@endforeach

</table>

</x-app-layout>