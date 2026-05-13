<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="dgcpt-card-title">Risk Registry</p>
                <h1 class="dgcpt-page-title">Enterprise Review Board</h1>
                <p class="mt-2 text-sm text-[#9FB3C8]">
                    File officielle de revue des risques détectés, validation humaine, promotion vers le registre canonique et gouvernance lifecycle.
                </p>
            </div>
            <a href="{{ route('cartographie.select') }}" class="dgcpt-btn-secondary">Voir la cartographie</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-[rgba(0,168,107,0.35)] bg-[#10192B] px-4 py-3 text-sm font-medium text-[#E6EEF8]">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-4">
            <div class="dgcpt-surface p-5">
                <div class="text-xs uppercase tracking-[0.2em] text-[#9FB3C8]">Critiques ouverts</div>
                <div class="mt-3 text-3xl font-semibold text-[#FF7B72]">{{ $dashboard['critical_open'] }}</div>
            </div>
            <div class="dgcpt-surface p-5">
                <div class="text-xs uppercase tracking-[0.2em] text-[#9FB3C8]">En revue</div>
                <div class="mt-3 text-3xl font-semibold text-[#F4D000]">{{ $dashboard['in_review'] }}</div>
            </div>
            <div class="dgcpt-surface p-5">
                <div class="text-xs uppercase tracking-[0.2em] text-[#9FB3C8]">Promus</div>
                <div class="mt-3 text-3xl font-semibold text-[#00D1FF]">{{ $dashboard['promoted'] }}</div>
            </div>
            <div class="dgcpt-surface p-5">
                <div class="text-xs uppercase tracking-[0.2em] text-[#9FB3C8]">Exposition résiduelle</div>
                <div class="mt-3 text-3xl font-semibold text-[#E6EEF8]">{{ $dashboard['residual_exposure'] }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="dgcpt-section-title">Queue de revue intake</h2>
                    <span class="rounded-full border border-[rgba(0,209,255,0.18)] px-3 py-1 text-xs font-semibold text-[#9BDCFF]">
                        {{ $intakeQueue->count() }} candidats
                    </span>
                </div>

                <div class="space-y-4">
                    @forelse($intakeQueue as $risk)
                        @php
                            $status = \App\Domain\Risk\Enums\RiskLifecycleStatus::fromMixed($risk->lifecycle_status)->value;
                            $badgeClass = match ($lifecycleColors[$status] ?? 'slate') {
                                'amber' => 'bg-amber-500/15 text-amber-200 border-amber-400/30',
                                'emerald' => 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
                                'cyan' => 'bg-cyan-500/15 text-cyan-200 border-cyan-400/30',
                                'blue' => 'bg-blue-500/15 text-blue-200 border-blue-400/30',
                                'green' => 'bg-green-500/15 text-green-200 border-green-400/30',
                                'rose' => 'bg-rose-500/15 text-rose-200 border-rose-400/30',
                                'zinc' => 'bg-zinc-500/15 text-zinc-200 border-zinc-400/30',
                                default => 'bg-slate-500/15 text-slate-200 border-slate-400/30',
                            };
                            $criticality = \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($risk->criticality);
                        @endphp

                        <article class="dgcpt-surface space-y-4 p-5">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-[#E6EEF8]">{{ $risk->title }}</h3>
                                        <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $badgeClass }}">
                                            {{ $lifecycleLabels[$status] ?? $status }}
                                        </span>
                                        @if ($criticality)
                                            <span class="rounded-full bg-[#0F2236] px-2.5 py-1 text-[11px] font-semibold text-[#9BDCFF]">
                                                {{ $criticality->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-[#9FB3C8]">{{ $risk->description ?: 'Aucune description détaillée.' }}</p>
                                </div>
                                <div class="text-right text-xs text-[#9FB3C8]">
                                    <div>Mission: {{ $risk->mission?->organisation ?? '—' }}</div>
                                    <div>Service: {{ $risk->service?->nom ?? '—' }}</div>
                                    <div>Maj: {{ optional($risk->updated_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-4 text-sm">
                                <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Catégorie: <strong>{{ $risk->category ?? '—' }}</strong></div>
                                <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Probabilité: <strong>{{ $risk->probability ?? '—' }}</strong></div>
                                <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Impact: <strong>{{ $risk->impact ?? '—' }}</strong></div>
                                <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Créé par: <strong>{{ $risk->creator?->displayName() ?? '—' }}</strong></div>
                            </div>

                            <div class="grid gap-3 xl:grid-cols-4">
                                <form method="POST" action="{{ route('identified-risks.submit-review', $risk) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Notes de revue"></textarea>
                                    <button type="submit" class="dgcpt-btn-secondary w-full justify-center text-sm">Soumettre en revue</button>
                                </form>
                                <form method="POST" action="{{ route('identified-risks.approve', $risk) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Notes d'approbation"></textarea>
                                    <button type="submit" class="dgcpt-btn-primary w-full justify-center text-sm">Approuver</button>
                                </form>
                                <form method="POST" action="{{ route('identified-risks.reject', $risk) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Motif de rejet"></textarea>
                                    <button type="submit" class="w-full rounded-lg border border-rose-400/30 bg-rose-500/10 px-4 py-2 text-sm font-semibold text-rose-200">Rejeter</button>
                                </form>
                                <form method="POST" action="{{ route('identified-risks.promote', $risk) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Commentaire de promotion"></textarea>
                                    <button type="submit" class="w-full rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-200">Promouvoir au registre</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="dgcpt-surface p-6 text-sm text-[#9FB3C8]">
                            Aucun risque intake en attente sur le board.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="dgcpt-section-title">Registre canonique</h2>
                    <span class="rounded-full border border-[rgba(0,209,255,0.18)] px-3 py-1 text-xs font-semibold text-[#9BDCFF]">
                        {{ $officialRisks->count() }} éléments récents
                    </span>
                </div>

                <div class="space-y-4">
                    @forelse($officialRisks as $risk)
                        @php
                            $status = \App\Domain\Risk\Enums\RiskLifecycleStatus::fromMixed($risk->lifecycle_status)->value;
                            $badgeClass = match ($lifecycleColors[$status] ?? 'slate') {
                                'amber' => 'bg-amber-500/15 text-amber-200 border-amber-400/30',
                                'emerald' => 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
                                'cyan' => 'bg-cyan-500/15 text-cyan-200 border-cyan-400/30',
                                'blue' => 'bg-blue-500/15 text-blue-200 border-blue-400/30',
                                'green' => 'bg-green-500/15 text-green-200 border-green-400/30',
                                'rose' => 'bg-rose-500/15 text-rose-200 border-rose-400/30',
                                'zinc' => 'bg-zinc-500/15 text-zinc-200 border-zinc-400/30',
                                default => 'bg-slate-500/15 text-slate-200 border-slate-400/30',
                            };
                            $criticality = \App\Domain\Risk\Enums\CriticalityLevel::fromMixed($risk->criticality);
                        @endphp

                        <article class="dgcpt-surface space-y-4 p-5">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-[#E6EEF8]">{{ $risk->risk_reference ?? ('RISK-'.$risk->id) }}</h3>
                                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $badgeClass }}">
                                        {{ $lifecycleLabels[$status] ?? $status }}
                                    </span>
                                    @if ($criticality)
                                        <span class="rounded-full bg-[#0F2236] px-2.5 py-1 text-[11px] font-semibold text-[#9BDCFF]">
                                            {{ $criticality->label() }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-[#D7E6F5]">{{ $risk->description }}</p>
                                <div class="grid gap-3 md:grid-cols-3 text-sm">
                                    <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Propriétaire: <strong>{{ $risk->owner?->displayName() ?? $risk->proprietaire ?? '—' }}</strong></div>
                                    <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Département: <strong>{{ $risk->ownerDepartment?->code ?? $risk->departement ?? '—' }}</strong></div>
                                    <div class="rounded-lg bg-[#0F2236] px-3 py-2 text-[#D7E6F5]">Résiduel: <strong>{{ $risk->residual_score ?? $risk->score_residuel ?? '—' }}</strong></div>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <form method="POST" action="{{ route('risques.assign-owner', $risk) }}" class="grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="owner_user_id" class="dgcpt-input" placeholder="ID propriétaire utilisateur" value="{{ old('owner_user_id', $risk->owner_user_id) }}">
                                    <input type="number" name="owner_department_id" class="dgcpt-input" placeholder="ID département" value="{{ old('owner_department_id', $risk->owner_department_id) }}">
                                    <button type="submit" class="dgcpt-btn-secondary justify-center">Assigner</button>
                                </form>

                                <div class="grid gap-3 md:grid-cols-3">
                                    <form method="POST" action="{{ route('risques.mitigate', $risk) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Notes de mitigation"></textarea>
                                        <button type="submit" class="dgcpt-btn-secondary w-full justify-center text-sm">Mitiger</button>
                                    </form>
                                    <form method="POST" action="{{ route('risques.close', $risk) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Notes de clôture"></textarea>
                                        <button type="submit" class="dgcpt-btn-primary w-full justify-center text-sm">Clôturer</button>
                                    </form>
                                    <form method="POST" action="{{ route('risques.archive', $risk) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="comment" rows="2" class="dgcpt-textarea text-sm" placeholder="Notes d'archivage"></textarea>
                                        <button type="submit" class="w-full rounded-lg border border-zinc-400/30 bg-zinc-500/10 px-4 py-2 text-sm font-semibold text-zinc-200">Archiver</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="dgcpt-surface p-6 text-sm text-[#9FB3C8]">
                            Aucun risque promu récent dans le registre.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
