<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-8 px-0 py-2">
        <div>
            <p class="dgcpt-card-title">Risques</p>
            <h1 class="dgcpt-page-title">Matrice ù {{ $actif->nom }}</h1>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-[rgba(0,168,107,0.4)] bg-[#10192B] px-4 py-3 text-sm font-medium text-[#E6EEF8]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-surface p-5 shadow-sm">
            <h2 class="dgcpt-section-title">Matrice de criticitù</h2>
            <p class="mt-2 text-sm dgcpt-text-muted">
                Criticitù = impact ù probabilitù (1ù25). Seuils : Faible ?6, Moyen 7ù12, ùlevù 13ù18, Critique ?19.
                Le risque rùsiduel est recalculù aprùs enregistrement d'un contrùle (efficacitù faible / moyenne / forte).
            </p>
            <p class="mt-2 text-sm text-[#9BDCFF]">
                Toute nouvelle saisie passe d?sormais par la cha?ne canonique <strong>IdentifiedRisk -> validation -> promotion -> Risque</strong> pour conserver la compatibilit? du module legacy.
            </p>
        </div>

        <div>
            <h2 class="dgcpt-section-title mb-3">Nouveau risque</h2>
            <form method="POST" action="{{ route('risques.store') }}" class="dgcpt-surface mb-8 space-y-4 p-5 shadow-sm">
                @csrf
                <input type="hidden" name="actif_id" value="{{ $actif->id }}">

                <div>
                    <label class="dgcpt-label">Description</label>
                    <input type="text" name="description" class="dgcpt-input" required maxlength="2000" value="{{ old('description') }}">
                    @error('description')<span class="mt-1 block text-sm text-[#FF5A5A]">{{ $message }}</span>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div>
                        <label class="dgcpt-label">Impact (1ù5)</label>
                        <input type="number" name="impact_inherent" min="1" max="5" class="dgcpt-input" required value="{{ old('impact_inherent') }}">
                    </div>
                    <div>
                        <label class="dgcpt-label">Probabilitù (1ù5)</label>
                        <input type="number" name="probabilite_inherent" min="1" max="5" class="dgcpt-input" required value="{{ old('probabilite_inherent') }}">
                    </div>
                    <div>
                        <label class="dgcpt-label">Propriùtaire du risque</label>
                        <input type="text" name="proprietaire" class="dgcpt-input" maxlength="255" value="{{ old('proprietaire') }}">
                    </div>
                    <div>
                        <label class="dgcpt-label">Dùpartement</label>
                        <input type="text" name="departement" class="dgcpt-input" maxlength="255" value="{{ old('departement') }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="dgcpt-label">Date de revue</label>
                        <input type="date" name="date_revue" class="dgcpt-input" value="{{ old('date_revue') }}">
                    </div>
                    <div>
                        <label class="dgcpt-label">Statut du risque</label>
                        <select name="statut_risque" class="dgcpt-select">
                            @foreach(\App\Domain\Risk\Enums\RiskStatus::cases() as $st)
                                <option value="{{ $st->value }}" @selected(old('statut_risque') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="dgcpt-label">Plan de mitigation</label>
                    <textarea name="plan_mitigation" rows="3" class="dgcpt-textarea" maxlength="10000">{{ old('plan_mitigation') }}</textarea>
                </div>

                <button type="submit" class="dgcpt-btn-primary">Ajouter le risque</button>
            </form>
        </div>

        <div>
            <h2 class="mb-3 text-base font-bold uppercase tracking-wider text-[#E6EEF8]">Risques enregistrùs</h2>
            <div class="dgcpt-table-wrap shadow-sm">
                <table class="dgcpt-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Score inh.</th>
                            <th>Crit. inh.</th>
                            <th>Score rùs.</th>
                            <th>Crit. rùs.</th>
                            <th>Propriùtaire</th>
                            <th>Dùpt.</th>
                            <th>Statut</th>
                            <th>Liens</th>
                            <th>Modifier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($risques as $r)
                            <tr>
                                <td>{{ $r->description }}</td>
                                <td class="text-center">{{ $r->score_inherent }}</td>
                                <td class="text-center">{{ \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($r->criticite_inherent)?->label() ?? 'ù' }}</td>
                                <td class="text-center">{{ $r->score_residuel ?? 'ù' }}</td>
                                <td class="text-center">{{ \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($r->criticite_residuel)?->label() ?? 'ù' }}</td>
                                <td>{{ $r->proprietaire ?? 'ù' }}</td>
                                <td>{{ $r->departement ?? 'ù' }}</td>
                                <td>{{ \App\Domain\Risk\Enums\RiskStatus::tryFrom($r->statut_risque ?? '')?->label() ?? $r->statut_risque }}</td>
                                <td class="text-xs">
                                    <a href="{{ route('actions.index', $r->id) }}" class="dgcpt-link">Actions</a><br>
                                    <a href="{{ route('controles.index', $r->id) }}" class="dgcpt-link">Contrùles</a>
                                </td>
                                <td>
                                    @can('update', $r)
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-[#00D1FF] hover:underline">ùditer</summary>
                                            <form method="POST" action="{{ route('risques.update', $r) }}" class="mt-2 space-y-2 rounded-lg border border-[rgba(0,209,255,0.18)] bg-[#10192B] p-3 text-xs">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="description" value="{{ $r->description }}" class="dgcpt-input text-xs" required>
                                                <div class="flex gap-2">
                                                    <input type="number" name="impact_inherent" min="1" max="5" value="{{ $r->impact_inherent }}" class="dgcpt-input w-20 text-xs">
                                                    <input type="number" name="probabilite_inherent" min="1" max="5" value="{{ $r->probabilite_inherent }}" class="dgcpt-input w-20 text-xs">
                                                </div>
                                                <input type="text" name="proprietaire" value="{{ $r->proprietaire }}" placeholder="Propriùtaire" class="dgcpt-input text-xs">
                                                <input type="text" name="departement" value="{{ $r->departement }}" placeholder="Dùpartement" class="dgcpt-input text-xs">
                                                <input type="date" name="date_revue" value="{{ $r->date_revue?->format('Y-m-d') }}" class="dgcpt-input text-xs">
                                                <select name="statut_risque" class="dgcpt-select text-xs">
                                                    @foreach(\App\Domain\Risk\Enums\RiskStatus::cases() as $st)
                                                        <option value="{{ $st->value }}" @selected($r->statut_risque === $st->value)>{{ $st->label() }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="plan_mitigation" rows="2" class="dgcpt-textarea text-xs" placeholder="Plan de mitigation">{{ $r->plan_mitigation }}</textarea>
                                                <button type="submit" class="dgcpt-btn-primary w-full justify-center py-2 text-xs">Enregistrer</button>
                                            </form>
                                        </details>
                                    @else
                                        <span class="text-xs text-[#F4D000]" title="Risque critique : rùservù Risk Manager / Admin">
                                            Verrouillù (critique)
                                        </span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-4 text-center text-[#9FB3C8]">Aucun risque pour cet actif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
