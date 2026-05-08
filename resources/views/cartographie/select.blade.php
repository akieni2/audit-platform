<x-app-layout>

<h2 style="margin-bottom:20px;">Cartographie des risques par mission</h2>

<p style="margin-bottom:20px;">
Sélectionnez une mission pour afficher sa cartographie des risques.
</p>

<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%;background:white;">

<tr style="background:#1e293b;color:white;">
<th>Mission / Organisation</th>
<th>Date début</th>
<th>Date fin</th>
<th>Action</th>
</tr>

@foreach($missions as $m)

<tr>

<td>
<strong>{{ $m->organisation }}</strong>
</td>

<td>{{ $m->date_debut }}</td>

<td>{{ $m->date_fin }}</td>

<td>

<a href="/missions/{{ $m->id }}/cartographie"
style="
background:#2563eb;
color:white;
padding:6px 12px;
text-decoration:none;
border-radius:4px;
">

Voir cartographie

</a>

</td>

</tr>

@endforeach

</table>

</x-app-layout>
