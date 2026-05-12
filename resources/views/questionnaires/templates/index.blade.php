<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6 px-0 py-2">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Bibliothèque institutionnelle</p>
                <h1 class="dgcpt-page-title">Modèles de questionnaires</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Référentiels dynamiques — sections et questions pilotées par données.</p>
            </div>
            @can('create', \App\Models\QuestionnaireTemplate::class)
                <a href="{{ route('questionnaire-templates.create') }}" class="dgcpt-btn-primary inline-flex">
                    Nouveau modèle
                </a>
            @endcan
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Type mission</th>
                        <th>Sections</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $t)
                        <tr>
                            <td class="font-semibold text-[#E6EEF8]">
                                <a href="{{ route('questionnaire-templates.edit', $t) }}" class="text-[#00D1FF] hover:underline">{{ $t->name }}</a>
                            </td>
                            <td class="font-mono text-xs text-[#9FB3C8]">{{ $t->slug }}</td>
                            <td class="text-[#9FB3C8]">{{ $t->mission_type ?: '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ $t->sections_count }}</td>
                            <td>
                                @if ($t->active)
                                    <span class="rounded bg-[#0A2A66]/80 px-2 py-0.5 text-xs font-semibold text-[#00D1FF]">Oui</span>
                                @else
                                    <span class="text-[#9FB3C8]">Non</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @can('duplicate', $t)
                                    <form method="POST" action="{{ route('questionnaire-templates.duplicate', $t) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="font-semibold text-[#9FB3C8] hover:text-[#00D1FF] hover:underline">Dupliquer</button>
                                    </form>
                                @endcan
                                @can('delete', $t)
                                    <form method="POST" action="{{ route('questionnaire-templates.destroy', $t) }}" class="inline ml-2" onsubmit="return confirm('Archiver ce modèle ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-[#FF5A5A] hover:underline">Archiver</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-[#9FB3C8]">Aucun modèle visible pour votre profil.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $templates->links() }}
    </div>
</x-app-layout>
