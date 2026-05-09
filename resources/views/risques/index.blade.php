<x-app-layout>

<script src="https://cdn.tailwindcss.com"></script>

<div style="max-width:1100px;">

<h2 class="text-xl font-semibold text-slate-800 mb-2">Actif : {{ $actif->nom }}</h2>

@if (session('status'))
    <p class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded px-3 py-2">{{ session('status') }}</p>
@endif

<h3 class="text-lg font-medium text-slate-700 mb-3">Matrice de criticité</h3>
<p class="text-sm text-slate-600 mb-4">
    Criticité = impact × probabilité (1–25). Seuils : Faible ?6, Moyen 7–12, Élevé 13–18, Critique ?19.
    Le risque résiduel est recalculé après enregistrement d’un contrôle (efficacité faible / moyenne / forte).
</p>

<h3 class="text-lg font-medium text-slate-700 mb-3">Nouveau risque</h3>

<form method="POST" action="{{ route('risques.store') }}" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 mb-8 space-y-3">
    @csrf
    <input type="hidden" name="actif_id" value="{{ $actif->id }}">

    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <input type="text" name="description" class="mt-1 w-full border border-slate-300 rounded px-3 py-2" required maxlength="2000" value="{{ old('description') }}">
        @error('description')<span class="text-red-600 text-sm">{{ $message }}</span>@enderror
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">Impact (1–5)</label>
            <input type="number" name="impact_inherent" min="1" max="5" class="mt-1 w-full border rounded px-3 py-2" required value="{{ old('impact_inherent') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Probabilité (1–5)</label>
            <input type="number" name="probabilite_inherent" min="1" max="5" class="mt-1 w-full border rounded px-3 py-2" required value="{{ old('probabilite_inherent') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Propriétaire du risque</label>
            <input type="text" name="proprietaire" class="mt-1 w-full border rounded px-3 py-2" maxlength="255" value="{{ old('proprietaire') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Département</label>
            <input type="text" name="departement" class="mt-1 w-full border rounded px-3 py-2" maxlength="255" value="{{ old('departement') }}">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">Date de revue</label>
            <input type="date" name="date_revue" class="mt-1 w-full border rounded px-3 py-2" value="{{ old('date_revue') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Statut du risque</label>
            <select name="statut_risque" class="mt-1 w-full border rounded px-3 py-2">
                @foreach(\App\Domain\Risk\Enums\RiskStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(old('statut_risque') === $st->value)>{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Plan de mitigation</label>
        <textarea name="plan_mitigation" rows="3" class="mt-1 w-full border rounded px-3 py-2" maxlength="10000">{{ old('plan_mitigation') }}</textarea>
    </div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-medium">Ajouter le risque</button>
</form>

<h3 class="text-lg font-medium text-slate-700 mb-3">Risques enregistrés</h3>

<div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-slate-200">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-slate-800 text-white">
                <th class="text-left p-2">Description</th>
                <th class="p-2">Score inh.</th>
                <th class="p-2">Crit. inh.</th>
                <th class="p-2">Score rés.</th>
                <th class="p-2">Crit. rés.</th>
                <th class="p-2">Propriétaire</th>
                <th class="p-2">Dépt.</th>
                <th class="p-2">Statut</th>
                <th class="p-2">Liens</th>
                <th class="p-2">Modifier</th>
            </tr>
        </thead>
        <tbody>
            @forelse($risques as $r)
                <tr class="border-b border-slate-100 align-top">
                    <td class="p-2">{{ $r->description }}</td>
                    <td class="p-2 text-center">{{ $r->score_inherent }}</td>
                    <td class="p-2 text-center">{{ \App\Domain\Risk\Enums\CriticalityLevel::tryFrom($r->criticite_inherent ?? '')?->label() ?? '—' }}</td>
                    <td class="p-2 text-center">{{ $r->score_residuel ?? '—' }}</td>
                    <td class="p-2 text-center">{{ \App\Domain\Risk\Enums\CriticalityLevel::tryFrom($r->criticite_residuel ?? '')?->label() ?? '—' }}</td>
                    <td class="p-2">{{ $r->proprietaire ?? '—' }}</td>
                    <td class="p-2">{{ $r->departement ?? '—' }}</td>
                    <td class="p-2">{{ \App\Domain\Risk\Enums\RiskStatus::tryFrom($r->statut_risque ?? '')?->label() ?? $r->statut_risque }}</td>
                    <td class="p-2 text-xs">
                        <a href="{{ route('actions.index', $r->id) }}" class="text-blue-600 underline">Actions</a><br>
                        <a href="{{ route('controles.index', $r->id) }}" class="text-blue-600 underline">Contrôles</a>
                    </td>
                    <td class="p-2">
                        @can('update', $r)
                            <details>
                                <summary class="cursor-pointer text-blue-600 text-xs">Éditer</summary>
                                <form method="POST" action="{{ route('risques.update', $r) }}" class="mt-2 space-y-2 text-xs bg-slate-50 p-2 rounded border">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="description" value="{{ $r->description }}" class="w-full border rounded px-2 py-1" required>
                                    <div class="flex gap-2">
                                        <input type="number" name="impact_inherent" min="1" max="5" value="{{ $r->impact_inherent }}" class="w-16 border rounded px-1">
                                        <input type="number" name="probabilite_inherent" min="1" max="5" value="{{ $r->probabilite_inherent }}" class="w-16 border rounded px-1">
                                    </div>
                                    <input type="text" name="proprietaire" value="{{ $r->proprietaire }}" placeholder="Propriétaire" class="w-full border rounded px-2 py-1">
                                    <input type="text" name="departement" value="{{ $r->departement }}" placeholder="Département" class="w-full border rounded px-2 py-1">
                                    <input type="date" name="date_revue" value="{{ $r->date_revue?->format('Y-m-d') }}" class="w-full border rounded px-2 py-1">
                                    <select name="statut_risque" class="w-full border rounded px-2 py-1">
                                        @foreach(\App\Domain\Risk\Enums\RiskStatus::cases() as $st)
                                            <option value="{{ $st->value }}" @selected($r->statut_risque === $st->value)>{{ $st->label() }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="plan_mitigation" rows="2" class="w-full border rounded px-2 py-1" placeholder="Plan de mitigation">{{ $r->plan_mitigation }}</textarea>
                                    <button type="submit" class="bg-slate-800 text-white px-2 py-1 rounded w-full">Enregistrer</button>
                                </form>
                            </details>
                        @else
                            <span class="text-xs text-amber-700" title="Risque critique : réservé Risk Manager / Admin">
                                Verrouillé (critique)
                            </span>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="p-4 text-center text-slate-500">Aucun risque pour cet actif.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>

</x-app-layout>
