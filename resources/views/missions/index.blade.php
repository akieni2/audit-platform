<x-app-layout>

<h2>Liste des missions</h2>

<a href="{{ route('missions.create') }}">Nouvelle mission</a>

<br><br>

<table border="1" cellpadding="8">

<tr>
<th>Organisation</th>
<th>Date dùbut</th>
<th>Date fin</th>
<th>Actions</th>
</tr>

@forelse($missions as $mission)

<tr>

<td>{{ $mission->organisation }}</td>
<td>{{ $mission->date_debut }}</td>
<td>{{ $mission->date_fin }}</td>

<td>

<a href="{{ route('services.index', $mission->id) }}">
Services
</a>

<br>

<a href="{{ route('cartographie.index', $mission->id) }}">
Cartographie
</a>

<br>

<a href="{{ route('missions.rapport', $mission->id) }}">
TÈlÈcharger rapport PDF
</a>

</td>

</tr>

@empty

<tr>
<td colspan="4" style="text-align:center;">
Aucune mission enregistrùe
</td>
</tr>

@endforelse

</table>

</x-app-layout>