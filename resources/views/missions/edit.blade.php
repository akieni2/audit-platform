<x-app-layout>

<h2>Editer mission</h2>

<form method="POST" action="/missions/{{ $mission->id }}">

@csrf
@method('PUT')

<label>Organisation</label>
<input type="text" name="organisation" value="{{ $mission->organisation }}">

<br>

<label>Description</label>
<textarea name="description">{{ $mission->description }}</textarea>

<br>

<label>Date début</label>
<input type="date" name="date_debut" value="{{ $mission->date_debut }}">

<br>

<label>Date fin</label>
<input type="date" name="date_fin" value="{{ $mission->date_fin }}">

<br><br>

<button type="submit">Mettre à jour</button>

</form>

</x-app-layout>
