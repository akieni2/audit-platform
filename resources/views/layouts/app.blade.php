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

@if(isset($sidebarDepartments) && $sidebarDepartments->isNotEmpty())
<br><br>
<b>Pôles / départements</b>
@foreach($sidebarDepartments as $dept)
<a href="{{ route('missions.index', ['department' => $dept->id]) }}"
   style="{{ (string) request()->query('department') === (string) $dept->id ? 'background:#334155;font-weight:bold;' : '' }}">
    {{ $dept->code }} — {{ \Illuminate\Support\Str::limit($dept->name, 28) }}
</a>
@endforeach
<a href="{{ route('missions.index') }}" style="opacity:.85;font-size:12px;">Toutes missions</a>
@endif

@can('manageUsers')
<br><br>
<b>Administration</b>
<a href="{{ route('admin.home') }}">Tableau de bord admin</a>
<a href="{{ route('admin.users.index') }}">Utilisateurs IAM</a>
<a href="{{ route('admin.security.audit-logs') }}">Journal sécurité</a>
@endcan

@if(auth()->check())
<br><br>
<b>Compte</b>
<a href="{{ route('profile.edit') }}">Mon profil</a>
<a href="{{ route('profile.security') }}">Sécurité</a>
<form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
    @csrf
    <button type="submit" style="width:100%;text-align:left;background:#b91c1c;color:white;border:none;padding:8px;border-radius:4px;cursor:pointer;font:inherit;">
        Déconnexion
    </button>
</form>
@endif

</div>

<div class="content">

{{ $slot }}

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
