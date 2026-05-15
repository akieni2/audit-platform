<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">RACI Runtime</p>
                <h1 class="dgcpt-page-title">{{ $mission->organisation }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">Matrice RACI missionnelle, responsabilites runtime et validation hierarchique.</p>
            </div>
            <a href="{{ route('raci.analytics', $mission) }}" class="dgcpt-btn-outline">Analytics RACI</a>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($raciView['kpis'] as $label => $value)
                <div class="dgcpt-surface p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-[#73D8FF]">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#E6EEF8]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @include('raci.runtime.assignments', [
            'mission' => $mission,
            'raciView' => $raciView,
            'raciTemplates' => $raciTemplates,
            'roleOptions' => $roleOptions,
            'userOptions' => $userOptions,
        ])

        @include('raci.runtime.validation', [
            'mission' => $mission,
            'raciView' => $raciView,
        ])

        <div class="dgcpt-surface overflow-hidden shadow-sm">
            <div class="px-6 py-5">
                <p class="dgcpt-card-title">Assignments</p>
                <h2 class="text-xl font-bold text-[#E6EEF8]">Vue actuelle</h2>
            </div>
            <table class="dgcpt-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-left">Processus</th>
                        <th class="text-left">Role</th>
                        <th class="text-left">Type</th>
                        <th class="text-left">Niveau</th>
                        <th class="text-left">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($raciView['assignments'] as $assignment)
                        <tr>
                            <td class="font-semibold text-[#E6EEF8]">{{ $assignment->process_label }}</td>
                            <td>{{ $assignment->raciRole?->name ?? 'Role' }}</td>
                            <td>{{ strtoupper(substr((string) $assignment->role_type, 0, 1)) }}</td>
                            <td>{{ $assignment->responsibility_level?->label() ?? $assignment->responsibility_level }}</td>
                            <td>{{ $assignment->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-[#9FB3C8]">Aucune affectation RACI enregistree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
