<!DOCTYPE html>
<html>
<head>

<title>Tableau de bord d’audit</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:40px;
}

.container{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
}

.card{
background:white;
padding:20px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.title{
font-size:14px;
color:gray;
}

.value{
font-size:28px;
font-weight:bold;
}

</style>

</head>

<body>

<h1>Tableau de bord d’audit</h1>

<div class="container">

<div class="card">
<div class="title">Missions</div>
<div class="value">{{ $missions }}</div>
</div>

<div class="card">
<div class="title">Risques Critiques</div>
<div class="value">{{ $risquesCritiques }}</div>
</div>

<div class="card">
<div class="title">Actions Ouvertes</div>
<div class="value">{{ $actionsOuvertes }}</div>
</div>

<div class="card">
<div class="title">Actions en cours</div>
<div class="value">{{ $actionsEnCours }}</div>
</div>

<div class="card">
<div class="title">Actions Fermées</div>
<div class="value">{{ $actionsFermees }}</div>
</div>

</div>

</body>
</html>
