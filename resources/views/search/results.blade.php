<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-10">
        <div>
            <p class="dgcpt-card-title">Recherche</p>
            <h1 class="dgcpt-page-title">Missions</h1>
            <p class="mt-1 text-sm text-[#9FB3C8]">Missions visibles pour votre périmètre (minimum 2 caractères).</p>
        </div>

        <form method="get" action="{{ route('search') }}" class="dgcpt-filter-bar">
            <div class="min-w-0 flex-1">
                <label class="dgcpt-card-title" for="q">Requête</label>
                <input id="q" name="q" type="search" value="{{ $term }}"
                       placeholder="Organisation, description…"
                       class="mt-1 block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/65 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" />
            </div>
            <button type="submit" class="dgcpt-btn-primary self-end sm:self-auto">Rechercher</button>
        </form>

        @if (strlen($term) < 2)
            <p class="text-sm text-[#9FB3C8]">Saisissez au moins 2 caractères pour lancer une recherche.</p>
        @elseif ($missions->isEmpty())
            <div class="dgcpt-surface px-4 py-6 text-center text-sm text-[#9FB3C8]">
                Aucune mission ne correspond à « {{ $term }} ».
            </div>
        @else
            <div class="dgcpt-table-wrap shadow-sm">
                <ul class="divide-y divide-[rgba(0,209,255,0.12)]">
                    @foreach ($missions as $m)
                        <li class="px-4 py-3 transition hover:bg-[#122038]">
                            <a href="{{ route('missions.show', $m) }}" class="font-semibold text-[#00D1FF] hover:underline">
                                {{ $m->organisation }}
                            </a>
                            <p class="mt-1 text-xs text-[#9FB3C8]">
                                {{ $m->department?->code ?? '—' }} · {{ $m->mission_status }} · MAJ {{ $m->updated_at?->format('d/m/Y H:i') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
