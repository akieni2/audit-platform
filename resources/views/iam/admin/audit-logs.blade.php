<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Sécurité</p>
                <h1 class="dgcpt-page-title">Journal de sécurité</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Traçabilité des opérations sensibles (OWASP).</p>
            </div>
            <a href="{{ route('admin.home') }}" class="dgcpt-link text-sm">← Administration</a>
        </div>

        <form method="get" action="{{ route('admin.security.audit-logs') }}" class="dgcpt-filter-bar">
            <div>
                <label class="dgcpt-card-title mb-1 block">Module</label>
                <input type="text" name="module" value="{{ $filters['module'] ?? '' }}" placeholder="auth, iam…"
                       class="rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/70 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" />
            </div>
            <div>
                <label class="dgcpt-card-title mb-1 block">Action</label>
                <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="login_success…"
                       class="rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-sm text-[#E6EEF8] placeholder:text-[#9FB3C8]/70 focus:border-[#00D1FF] focus:outline-none focus:ring-1 focus:ring-[#00D1FF]" />
            </div>
            <button type="submit" class="rounded-xl bg-[#10192B] px-4 py-2 text-sm font-bold uppercase tracking-wider text-[#E6EEF8] ring-1 ring-[rgba(0,209,255,0.25)] hover:bg-[#122038]">Filtrer</button>
            <a href="{{ route('admin.security.audit-logs') }}" class="dgcpt-link py-2 text-sm">Réinitialiser</a>
        </form>

        <div class="dgcpt-table-wrap shadow-sm">
            <table class="dgcpt-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-[#9FB3C8]">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="text-[#E6EEF8]">{{ $log->user?->email ?? '—' }}</td>
                            <td class="font-semibold text-[#E6EEF8]">{{ $log->action }}</td>
                            <td class="text-[#9FB3C8]">{{ $log->module }}</td>
                            <td class="whitespace-nowrap text-[#9FB3C8]">{{ $log->ip ?? '—' }}</td>
                            <td class="text-[#9FB3C8]">{{ \Illuminate\Support\Str::limit($log->description ?? '', 160) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-[#9FB3C8]">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
