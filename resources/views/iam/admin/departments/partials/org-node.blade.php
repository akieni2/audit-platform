@php
    $profile = $department->intelligence_profile ?? [];
    $activities = data_get($profile, 'position_activities', []);
@endphp

<div class="rounded-lg border border-[rgba(0,209,255,0.14)] bg-[rgba(7,18,32,0.72)] p-4" style="margin-left: {{ min($level * 18, 72) }}px">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-[#E6EEF8]">{{ $department->name }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-[#73D8FF]">{{ $department->code }} · {{ $department->typeLabel() }}</p>
        </div>
        <span class="rounded-full bg-[rgba(0,209,255,0.12)] px-2 py-1 text-[11px] font-semibold text-[#73D8FF]">{{ $department->children->count() }} sous-structures</span>
    </div>
    <div class="mt-3 grid gap-2 text-xs text-[#BFD2E6]">
        <p>Fonction dirigeante : {{ $department->headTitle() }}</p>
        <p>Titulaire : {{ $department->supervisor?->displayName() ?? data_get($profile, 'top_manager_profile.name', 'Non défini') }}</p>
        @if (data_get($profile, 'position_title'))
            <p>Poste clé: {{ data_get($profile, 'position_title') }}</p>
        @endif
        @if ($activities)
            <p>Activités: {{ implode(', ', array_slice($activities, 0, 3)) }}</p>
        @endif
    </div>
    @if ($department->children->isNotEmpty())
        <div class="mt-4 space-y-3 border-l border-[rgba(0,209,255,0.18)] pl-3">
            @foreach ($department->children as $child)
                @include('iam.admin.departments.partials.org-node', ['department' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
