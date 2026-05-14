<div class="sidebar">

<h3>Audit System</h3>

<ul>

<li><a href="{{ route('dashboard') }}">Dashboard</a></li>

<li>Missions
<ul>
<li><a href="{{ route('missions.index') }}">Liste missions</a></li>
<li><a href="{{ route('missions.create') }}">Nouvelle mission</a></li>
</ul>
</li>

<li><a href="{{ route('module.entretiens') }}">Entretiens</a></li>

<li><a href="{{ route('module.processus') }}">Processus</a></li>

<li><a href="{{ route('module.actifs') }}">Actifs</a></li>

<li><a href="{{ route('module.risques') }}">Risques</a></li>

<li><a href="{{ route('risks.review-board') }}">Review board</a></li>

<li><a href="{{ route('module.actions') }}">Actions correctives</a></li>

<li><a href="{{ route('questionnaire-builder.index') }}">Questionnaires</a></li>

<li><a href="{{ route('workflow-builder.index') }}">Workflows</a></li>

<li><a href="{{ route('workflow-runtime.dashboard') }}">Runtime workflows</a></li>

<li><a href="{{ route('enterprise.methodologies') }}">Méthodologies</a></li>

<li><a href="{{ route('enterprise.taxonomies') }}">Taxonomies</a></li>

<li><a href="{{ route('enterprise.controls') }}">Contrôles</a></li>

<li><a href="{{ route('form-builder.index') }}">Formulaires</a></li>

<li><a href="{{ route('module.rapports') }}">Rapports</a></li>

<li><a href="{{ route('enterprise.consolidation') }}">Consolidation</a></li>

@can('viewExecutiveDashboard')
<li><a href="{{ route('executive.national-dashboard') }}">Executive</a></li>
<li><a href="{{ route('executive.risk-intelligence') }}">Intelligence</a></li>
<li><a href="{{ route('executive.governance-overview') }}">Analytics</a></li>
@endcan

</ul>

</div>
