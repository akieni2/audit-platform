<x-app-layout>

<h2>Liste des missions</h2>

<a href="/missions/create">? Nouvelle mission</a>

<br><br>

<table border="1" cellpadding="8">

<tr>
<th>Organisation</th>
<th>Date début</th>
<th>Date fin</th>
<th>Actions</th>
</tr>

@forelse($missions as $mission)

<tr>

<td>{{ $mission->organisation }}</td>
<td>{{ $mission->date_debut }}</td>
<td>{{ $mission->date_fin }}</td>

<td>

<a href="/missions/{{ $mission->id }}/services">
?? Services
</a>

<br>

<a href="/missions/{{ $mission->id }}/cartographie">
?? Cartographie
</a>

<br>

<a href="/missions/{{ $mission->id }}/rapport">
?? Télécharger rapport PDF
</a>

</td>

</tr>

@empty

<tr>
<td colspan="4" style="text-align:center;">
Aucune mission enregistrée
</td>
</tr>

@endforelse

</table>

</x-app-layout>