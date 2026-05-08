<x-app-layout>

<h2>Créer une mission d'audit</h2>

<form method="POST" action="/missions">

@csrf

<label>Organisation</label>
<input type="text" name="organisation">

<br>

<label>Description</label>
<textarea name="description"></textarea>

<br>

<label>Date début</label>
<input type="date" name="date_debut">

<br>

<label>Date fin</label>
<input type="date" name="date_fin">

<br><br>

<button type="submit">Créer mission</button>

</form>

</x-app-layout>
