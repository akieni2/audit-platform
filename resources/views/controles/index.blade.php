<x-app-layout>

<h2>Evaluation des contrôles</h2>

<h3>Risque : {{ $risque->description }}</h3>

<form method="POST" action="/controles">

@csrf

<input type="hidden" name="risque_id" value="{{ $risque->id }}">

<label>Description du contrôle</label>
<br>
<input type="text" name="description" required>

<br><br>

<label>Type de contrôle</label>
<br>
<select name="type">

<option value="preventif">Préventif</option>
<option value="detectif">Détectif</option>
<option value="correctif">Correctif</option>

</select>

<br><br>

<label>Efficacité du contrôle</label>
<br>

<select name="efficacite">

<option value="faible">Faible</option>
<option value="moyenne">Moyenne</option>
<option value="forte">Forte</option>

</select>

<br><br>

<label>Commentaire de l'auditeur</label>
<br>
<textarea name="commentaire"></textarea>

<br><br>

<button type="submit">Ajouter contrôle</button>

</form>

<br>

<table border="1" cellpadding="10">

<tr>
<th>Description</th>
<th>Type</th>
<th>Efficacité</th>
<th>Commentaire</th>
</tr>

@foreach($controles as $c)

<tr>

<td>{{ $c->description }}</td>

<td>{{ $c->type }}</td>

<td>{{ $c->efficacite }}</td>

<td>{{ $c->commentaire }}</td>

</tr>

@endforeach

</table>

</x-app-layout>
