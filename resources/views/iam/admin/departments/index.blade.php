<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Organigramme</p>
                <h1 class="dgcpt-page-title">Structures DGCPT</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Directions, départements, services, responsables, postes et référentiels rattachés.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.departments.organigramme') }}" class="dgcpt-btn-outline">Voir organigramme</a>
                <a href="{{ route('enterprise.methodologies') }}" class="dgcpt-btn-outline">Gestion des référentiels</a>
                @can('create', \App\Models\Department::class)
                    <a href="{{ route('admin.departments.create') }}" class="dgcpt-btn-primary">Nouvelle structure</a>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-surface p-6">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-lg font-bold text-[#E6EEF8]">Organigramme commun</p>
                    <p class="text-sm text-[#9FB3C8]">La Direction générale reste le sommet, chaque structure étant positionnée au-dessus ou en dessous d’une autre.</p>
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($departmentTree as $root)
                    @include('iam.admin.departments.partials.org-node', ['department' => $root, 'level' => 0])
                @endforeach
            </div>
        </div>

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Niveau organisationnel</th>
                        <th>Rattachement</th>
                        <th>Responsable hiérarchique</th>
                        <th>Référentiel / espace d’audit</th>
                        <th class="text-right">Utilisateurs actifs</th>
                        <th class="text-right">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departments as $d)
                        <tr>
                            <td class="font-semibold text-[#00D1FF]">{{ $d->code }}</td>
                            <td class="text-[#E6EEF8]">{{ $d->name }}</td>
                            <td class="text-[#9FB3C8]">{{ $d->typeLabel() }}</td>
                            <td class="text-[#9FB3C8]">{{ $d->parent?->code ?? 'Sommet' }}</td>
                            <td class="text-[#9FB3C8]">{{ $d->supervisor?->displayName() ?? data_get($d->intelligence_profile, 'top_manager_profile.title', 'Non défini') }}</td>
                            <td class="text-[#9FB3C8]">
                                <p>{{ $d->defaultMethodologyTemplate?->name ?? 'Non configuré' }}</p>
                                @if (data_get($d->intelligence_profile, 'audit_environment.status') === 'ready' && $d->tenantContext?->active)
                                    <span class="text-xs font-semibold text-[#00A86B]">Espace d’audit prêt</span>
                                @else
                                    <span class="text-xs font-semibold text-[#FFB020]">Configuration requise</span>
                                @endif
                            </td>
                            <td class="text-right font-semibold text-[#E6EEF8]">{{ $d->users_count }}</td>
                            <td class="text-right">
                                @if ($d->active)
                                    <span class="inline-flex rounded-lg border border-[rgba(0,168,107,0.35)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#00A86B]">Actif</span>
                                @else
                                    <span class="inline-flex rounded-lg border border-[rgba(148,163,184,0.3)] bg-[#10192B] px-2 py-0.5 text-xs font-semibold text-[#9FB3C8]">Inactif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.departments.edit', $d) }}" class="text-xs font-semibold text-[#00D1FF] hover:underline">Modifier</a>
                                @can('delete', $d)
                                    <form method="post" action="{{ route('admin.departments.destroy', $d) }}" class="ml-2 inline" onsubmit="const value=prompt('Suppression définitive de cette structure et de toutes ses sous-structures. Saisissez le code {{ $d->code }} pour confirmer.'); if(value===null)return false; this.confirmation_code.value=value; return true;">
                                        @csrf
                                        @method('delete')
                                        <input type="hidden" name="confirmation_code" value="">
                                        <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-[#FF5A5A] hover:underline" title="Supprimer définitivement la structure">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12zM10 11v6M14 11v6"/></svg>
                                            Supprimer définitivement
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-[#9FB3C8]">
            {{ $departments->links() }}
        </div>
    </div>
</x-app-layout>
