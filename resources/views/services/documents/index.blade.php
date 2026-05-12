<x-app-layout>
    @php
        /** @var \App\Models\Mission $mission */
        /** @var \App\Models\MissionService $service */
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $documents */
    @endphp

    <div class="mx-auto max-w-5xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">Porte-documents</p>
                <h1 class="dgcpt-page-title">{{ $service->nom }}</h1>
                <p class="text-sm text-[#9FB3C8]">{{ $mission->organisation }}</p>
            </div>
            <a href="{{ route('services.index', $mission) }}" class="dgcpt-btn-outline text-sm">← Services</a>
        </div>

        @can('contribute', $service)
            <div class="dgcpt-surface p-6 shadow-sm">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Téléverser</h2>
                <form method="POST" action="{{ route('missions.services.documents.store', [$mission, $service]) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="dgcpt-label" for="file">Fichier (PDF, Office, images, ZIP — max 20 Mo)</label>
                        <input id="file" name="file" type="file" required class="mt-1 block w-full text-sm text-[#E6EEF8]" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="dgcpt-label" for="category">Catégorie</label>
                            <input id="category" name="category" type="text" class="dgcpt-input" placeholder="ex. preuve, procédure" />
                        </div>
                    </div>
                    <div>
                        <label class="dgcpt-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="2" class="dgcpt-textarea w-full"></textarea>
                    </div>
                    <button type="submit" class="dgcpt-btn-primary">Envoyer</button>
                </form>
            </div>
        @endcan

        <div class="dgcpt-surface overflow-hidden p-0 shadow-sm">
            <div class="border-b border-[rgba(0,209,255,0.12)] px-6 py-4">
                <h2 class="text-lg font-bold uppercase tracking-wide text-[#E6EEF8]">Fichiers</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="dgcpt-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Nom</th>
                            <th class="text-left">Type</th>
                            <th class="text-right">Taille</th>
                            <th class="text-left">Déposé par</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-semibold text-[#E6EEF8]">{{ $doc->original_name }}</td>
                                <td class="font-mono text-xs text-[#9FB3C8]">{{ $doc->mime_type ?: '—' }}</td>
                                <td class="text-right text-[#9FB3C8]">{{ number_format($doc->size / 1024, 1) }} Ko</td>
                                <td class="text-[#9FB3C8]">{{ $doc->uploader?->displayName() ?? '—' }}</td>
                                <td class="text-right">
                                    @can('delete', $doc)
                                        <form method="POST" action="{{ route('mission-documents.destroy', $doc) }}" class="inline" onsubmit="return confirm('Supprimer ce document ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-[#FF5A5A] hover:underline">Supprimer</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#9FB3C8]">Aucun document pour ce service.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[rgba(0,209,255,0.08)] px-4 py-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
