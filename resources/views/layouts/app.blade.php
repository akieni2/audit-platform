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

.app-topbar{
background:linear-gradient(90deg,#1e293b 0%,#334155 100%);
color:#fff;
padding:14px 18px;
margin:-20px -20px 18px -20px;
display:flex;
justify-content:space-between;
align-items:flex-start;
flex-wrap:wrap;
gap:12px;
border-bottom:3px solid #2563eb;
}

.app-topbar .welcome-badge{
display:inline-block;
background:#059669;
padding:5px 12px;
border-radius:6px;
font-size:13px;
margin-right:10px;
font-weight:600;
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
<b>Gestion des utilisateurs</b>
<a href="{{ route('admin.users.index') }}">Liste des utilisateurs</a>
<a href="{{ route('admin.users.create') }}" style="background:#059669;font-weight:bold;">+ Créer un utilisateur</a>
<br><br>
<b>Administration système</b>
<a href="{{ route('admin.home') }}">Tableau de bord admin</a>
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

@auth
@php
    auth()->user()->loadMissing('department', 'institutionalRole');
@endphp
<div class="app-topbar">
    <div>
        @if(session('welcome_once'))
            <span class="welcome-badge">{{ session('welcome_once') }}</span>
        @endif
        <span style="font-size:16px;"><strong>{{ auth()->user()->displayName() }}</strong></span>
        @if(auth()->user()->institutionalRole)
            <span style="opacity:.88;font-size:13px;margin-left:10px;">{{ auth()->user()->institutionalRole->name }}</span>
        @endif
    </div>
    <div style="text-align:right;max-width:520px;">
        @if(auth()->user()->department)
            <div style="font-size:12px;opacity:.85;text-transform:uppercase;letter-spacing:.04em;">Pôle / département</div>
            <div style="font-size:15px;margin-top:4px;">
                <strong>{{ auth()->user()->department->code }}</strong>
                <span style="opacity:.9;"> — {{ auth()->user()->department->name }}</span>
            </div>
        @else
            <div style="font-size:13px;opacity:.75;">Aucun département affecté — demandez une affectation à l’administration.</div>
        @endif
    </div>
</div>
@endauth

{{ $slot }}

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
