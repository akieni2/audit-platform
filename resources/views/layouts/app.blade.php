<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Audit Platform</title>

<style>

body{
display:flex;
margin:0;
font-family:Arial;
}

.sidebar{
width:220px;
background:#1e293b;
color:white;
min-height:100vh;
padding:20px;
}

.sidebar a{
color:white;
text-decoration:none;
display:block;
padding:8px;
}

.sidebar a:hover{
background:#334155;
}

.content{
flex:1;
padding:20px;
background:#f1f5f9;
}

</style>

</head>

<body>

<div class="sidebar">

<h3>Audit Platform</h3>

<a href="/dashboard">Dashboard</a>

<b>Missions</b>
<a href="/missions">Liste missions</a>
<a href="/missions/create">Nouvelle mission</a>

<br>

<b>Audit</b>

<a href="/missions">Services audités</a>
<a href="/cartographie">Cartographie des risques</a>

<br>

<b>Analyse</b>

<a href="#">Entretiens</a>
<a href="#">Processus</a>
<a href="#">Actifs</a>
<a href="#">Risques</a>

<br>

<b>Suivi</b>

<a href="#">Actions correctives</a>
<a href="#">Rapports</a>

@if(auth()->check() && auth()->user()->isAdmin())
<br><br>
<b>Administration</b>
<a href="{{ route('admin.users.index') }}">Rôles utilisateurs</a>
@endif

</div>

<div class="content">

{{ $slot }}

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
