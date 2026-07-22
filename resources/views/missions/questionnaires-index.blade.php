<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8]">{{ session('status') }}</div>
        @endif

        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Mission · {{ $mission->reference ?: 'sans référence' }}</p>
                <h1 class="dgcpt-page-title">Questionnaires de la mission</h1>
                <p class="mt-2 text-sm text-[#9FB3C8]">{{ $mission->organisation }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can('createQuestionnaire', $mission)
                    <a href="{{ route('missions.questionnaires.wizard.create', $mission) }}" class="dgcpt-btn-primary">Assistant de création visuelle</a>
                @endcan
                <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline">Retour à la fiche</a>
            </div>
        </header>

        <section class="dgcpt-surface p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="dgcpt-card-title">Création collaborative</p>
                    <h2 class="mt-1 text-xl font-bold text-[#E6EEF8]">Questionnaires construits pour cette mission</h2>
                    <p class="mt-1 text-sm text-[#9FB3C8]">Les inspecteurs peuvent créer, corriger et approuver ensemble les thèmes, thématiques, sous-thèmes et questions.</p>
                </div>
                @can('createQuestionnaire', $mission)
                    <a href="{{ route('missions.questionnaires.wizard.create', $mission) }}" class="dgcpt-btn-outline">Créer un questionnaire</a>
                @endcan
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @forelse ($mission->questionnaireTemplates as $template)
                    @php
                        $approvals = $template->reviews->where('decision', \App\Models\QuestionnaireTemplateReview::DECISION_APPROVED)->count();
                        $changes = $template->reviews->where('decision', \App\Models\QuestionnaireTemplateReview::DECISION_CHANGES_REQUESTED)->count();
                    @endphp
                    <article class="rounded-xl border border-[rgba(0,209,255,.18)] bg-[#071220] p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-[#E6EEF8]">{{ $template->name }}</h3>
                                <p class="mt-1 text-xs text-[#9FB3C8]">Créé par {{ $template->creator?->displayName() ?? '—' }}</p>
                            </div>
                            <span class="rounded-full bg-[rgba(0,209,255,.12)] px-3 py-1 text-xs font-semibold text-[#73D8FF]">{{ $template->reviewStatusLabel() }}</span>
                        </div>
                        <p class="mt-4 text-sm text-[#BFD2E6]">{{ $template->sections->count() }} éléments · {{ $approvals }} approbation(s) · {{ $changes }} correction(s) demandée(s)</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            @can('update', $template)
                                <a href="{{ route('questionnaire-builder.edit', $template) }}" class="dgcpt-btn-primary">Relire ou modifier</a>
                            @else
                                <a href="{{ route('questionnaire-builder.edit', $template) }}" class="dgcpt-btn-outline">Consulter</a>
                            @endcan
                            <a href="{{ route('missions.show', $mission) }}#questionnaires-collaboratifs" class="dgcpt-btn-outline">Avis et adoption</a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-[rgba(0,209,255,.22)] p-6 text-sm text-[#9FB3C8] md:col-span-2">
                        Aucun questionnaire n’a encore été créé pour cette mission.
                        @can('createQuestionnaire', $mission)
                            Utilisez le bouton « Assistant de création visuelle » pour commencer.
                        @endcan
                    </div>
                @endforelse
            </div>
        </section>

        <section class="dgcpt-surface p-6">
            <p class="dgcpt-card-title">Affectations opérationnelles</p>
            <h2 class="mt-1 text-xl font-bold text-[#E6EEF8]">Questionnaires attribués aux équipes</h2>
            <div class="mt-5 overflow-x-auto">
                <table class="dgcpt-table min-w-full">
                    <thead><tr><th>Groupe</th><th>Questionnaire</th><th>Inspecteurs</th><th>État</th></tr></thead>
                    <tbody>
                        @forelse ($mission->auditGroups as $group)
                            <tr>
                                <td class="font-semibold text-[#E6EEF8]">{{ $group->name }}</td>
                                <td class="text-[#73D8FF]">{{ $group->questionnaireTemplate?->name ?? '—' }}</td>
                                <td class="text-[#9FB3C8]">{{ $group->members->map(fn ($member) => $member->user?->displayName())->filter()->join(', ') ?: '—' }}</td>
                                <td class="text-[#9FB3C8]">{{ ucfirst(str_replace('_', ' ', $group->status ?: 'planned')) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-[#9FB3C8]">Aucun questionnaire n’est encore attribué à une équipe.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('assignTeamMembers', $mission)
                <a href="{{ route('missions.show', $mission) }}" class="dgcpt-btn-outline mt-4">Constituer les groupes d’audit</a>
            @endcan
        </section>
    </div>
</x-app-layout>
