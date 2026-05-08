<x-app-layout>

<h2>Constats d'audit</h2>

<h3>Risque : {{ $risque->description }}</h3>

<br>

<form method="POST" action="/constats">

@csrf

<input type="hidden" name="risque_id" value="{{ $risque->id }}">

<label>Description du constat</label><br>
<textarea name="description" style="width:400px;height:80px"></textarea>

<br><br>

<label>Gravité</label><br>

<select name="gravite">

<option value="faible">Faible</option>
<option value="moyenne">Moyenne</option>
<option value="elevee">Elevée</option>
<option value="critique">Critique</option>

</select>

<br><br>

<button type="submit">Ajouter constat</button>

</form>

<br><br>

<table border="1" cellpadding="8">

<tr>
<th>Description</th>
<th>Gravité</th>
<th>Actions</th>
</tr>

@foreach($constats as $c)

<tr>

<td>{{ $c->description }}</td>

<td>{{ $c->gravite }}</td>

<td>

<a href="/constats/{{ $c->id }}/actions">
📋 Actions correctives
</a>

</td>

</tr>

@endforeach

</table>

</x-app-layout>
