@php $profile = $department->intelligence_profile ?? []; @endphp

<div class="org-node-wrap space-y-3" data-org-existing="{{ $department->id }}" draggable="{{ ($builder ?? false) ? 'true' : 'false' }}">
    <div class="rounded-xl border border-[rgba(0,209,255,.2)] bg-[linear-gradient(135deg,rgba(7,18,32,.96),rgba(11,31,53,.92))] p-4 shadow-lg"
         data-org-drop-target="{{ $department->id }}" data-org-drop-name="{{ $department->code }} — {{ $department->name }}">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="mt-1 flex h-9 w-9 items-center justify-center rounded-full border border-[rgba(0,209,255,.35)] bg-[rgba(0,209,255,.1)] text-[#00D1FF]">◇</span>
                <div>
                    <p class="text-sm font-bold text-[#E6EEF8]">{{ $department->name }}</p>
                    <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $department->code }} · {{ $department->typeLabel() }}</p>
                </div>
            </div>
            <span class="rounded-full bg-[rgba(0,209,255,.12)] px-2 py-1 text-[11px] font-semibold text-[#73D8FF]">{{ $department->children->count() }} liens</span>
        </div>
        <div class="mt-3 grid gap-1 text-xs text-[#BFD2E6] sm:grid-cols-2">
            <p><span class="text-[#7F94AA]">Fonction :</span> {{ $department->headTitle() }}</p>
            <p><span class="text-[#7F94AA]">Titulaire :</span> {{ $department->supervisor?->displayName() ?? data_get($profile, 'top_manager_profile.name', 'Non défini') }}</p>
        </div>
        @if ($builder ?? false)<p class="mt-3 text-[11px] text-[#607D99]">☰ Glissez cette carte pour la déplacer · Déposez un objet ici pour le rattacher</p>@endif
    </div>

    @if ($department->children->isNotEmpty())
        <div class="org-children ml-5 space-y-3 border-l border-[rgba(0,209,255,.18)] pl-4">
            @foreach ($department->children as $child)
                @include('iam.admin.departments.partials.org-node', ['department' => $child, 'level' => $level + 1, 'builder' => $builder ?? false])
            @endforeach
        </div>
    @endif
</div>
