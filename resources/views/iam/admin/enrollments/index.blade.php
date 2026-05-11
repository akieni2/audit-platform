<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Super administration</p>
                <h1 class="dgcpt-page-title">Demandes d'enrôlement</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">
                    Validation — comptes issus de l'inscription publique (aucun accès avant approbation).
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.enrollments.index', ['status' => 'pending']) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition {{ ($status ?? 'pending') === 'pending' ? 'bg-[#122038] text-[#E6EEF8] ring-[rgba(0,209,255,0.45)]' : 'border border-[rgba(0,209,255,0.18)] bg-[#0B1220] text-[#9FB3C8] hover:bg-[#122038]' }}">
                    En attente
                </a>
                <a href="{{ route('admin.enrollments.index', ['status' => 'rejected']) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition {{ ($status ?? '') === 'rejected' ? 'bg-[#122038] text-[#E6EEF8] ring-[rgba(0,209,255,0.45)]' : 'border border-[rgba(0,209,255,0.18)] bg-[#0B1220] text-[#9FB3C8] hover:bg-[#122038]' }}">
                    Rejetées
                </a>
                <a href="{{ route('admin.enrollments.index', ['status' => 'all']) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition {{ ($status ?? '') === 'all' ? 'bg-[#122038] text-[#E6EEF8] ring-[rgba(0,209,255,0.45)]' : 'border border-[rgba(0,209,255,0.18)] bg-[#0B1220] text-[#9FB3C8] hover:bg-[#122038]' }}">
                    Toutes
                </a>
                <a href="{{ route('admin.home') }}" class="self-center text-sm font-semibold text-[#00D1FF] hover:underline">
                    Console admin
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="dgcpt-surface border-[#FF5A5A]/40 px-4 py-3 text-sm text-[#FF5A5A] ring-1 ring-[rgba(255,90,90,0.2)]">
                <ul class="ms-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Département demandé</th>
                        <th>Fonction</th>
                        <th>Matricule</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td class="font-semibold text-[#E6EEF8]">{{ $u->name }}</td>
                            <td class="text-[#9FB3C8]">{{ $u->prenom ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ $u->email }}</td>
                            <td class="text-[#9FB3C8]">{{ $u->telephone ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">
                                {{ $u->registrationRequestedDepartment?->code ?? '—' }}
                            </td>
                            <td class="text-[#9FB3C8]">{{ $u->fonction ?? $u->position ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ $u->matricule ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ $u->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="inline-flex rounded-lg px-2 py-0.5 text-xs font-bold uppercase tracking-wide ring-1
                                    {{ $u->approval_status === 'pending' ? 'bg-[#10192B] text-[#F4D000] ring-[rgba(244,208,0,0.35)]' : '' }}
                                    {{ $u->approval_status === 'rejected' ? 'bg-[#10192B] text-[#FF5A5A] ring-[rgba(255,90,90,0.35)]' : '' }}
                                    {{ $u->approval_status === 'approved' ? 'bg-[#10192B] text-[#00A86B] ring-[rgba(0,168,107,0.35)]' : '' }}">
                                    {{ $u->approval_status }}
                                </span>
                            </td>
                            <td>
                                @if ($u->isPendingApproval())
                                    <a href="{{ route('admin.enrollments.review', $u) }}" class="font-semibold text-[#00D1FF] hover:underline">Traiter</a>
                                @else
                                    <span class="text-[#9FB3C8]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-[#9FB3C8]">Aucune demande dans cette liste.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="text-[#9FB3C8]">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
