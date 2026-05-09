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

<a href="{{ route('dashboard') }}">Dashboard</a>

@can('viewExecutiveDashboard')
<a href="{{ route('dashboard.executive') }}">Tableau de bord exécutif</a>
@endcan

<b>Missions</b>
<a href="{{ route('missions.index') }}">Liste missions</a>
<a href="{{ route('missions.create') }}">Nouvelle mission</a>

<br>

<b>Audit</b>

<a href="{{ route('missions.index') }}">Services audités</a>
<a href="{{ route('cartographie.select') }}">Cartographie des risques</a>

<br>

<b>Analyse</b>

<a href="{{ route('module.entretiens') }}">Entretiens</a>
<a href="{{ route('module.processus') }}">Processus</a>
<a href="{{ route('module.actifs') }}">Actifs</a>
<a href="{{ route('module.risques') }}">Risques</a>

<br>

<b>Suivi</b>

<a href="{{ route('module.actions') }}">Actions correctives</a>
<a href="{{ route('module.rapports') }}">Rapports</a>

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
