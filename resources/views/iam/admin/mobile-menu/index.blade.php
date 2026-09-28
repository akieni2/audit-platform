<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-0 py-2 sm:px-0">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="dgcpt-card-title">Administration mobile</p>
                <h1 class="dgcpt-page-title">Menus de l’application Android</h1>
                <p class="mt-1 text-sm dgcpt-text-muted">Affectez une sélection de modules à une personne, un rôle ou une structure. Les droits du navigateur web ne sont pas modifiés.</p>
            </div>
            <a href="{{ route('admin.home') }}" class="dgcpt-link text-sm">Retour à l’administration</a>
        </div>

        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="dgcpt-surface border-[#FF5A5A]/40 px-4 py-3 text-sm text-[#FF8A8A]">
                <ul class="ms-5 list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.mobile-menu.store') }}" class="dgcpt-surface space-y-6 p-5" x-data="{ scope: @js(old('subject_type', 'user')) }">
            @csrf
            <div class="grid gap-4 lg:grid-cols-3">
                <div>
                    <label class="dgcpt-card-title mb-2 block" for="subject_type">Type d’affectation</label>
                    <select id="subject_type" name="subject_type" x-model="scope" class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-[#E6EEF8]">
                        <option value="user">Une personne</option>
                        <option value="role">Un rôle / groupe métier</option>
                        <option value="department">Une structure et ses descendants</option>
                    </select>
                </div>
                <div>
                    <label class="dgcpt-card-title mb-2 block">Personne, rôle ou structure</label>
                    <select name="subject_id" x-show="scope === 'user'" :disabled="scope !== 'user'" required class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-[#E6EEF8]">
                        @foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->displayName() }} — {{ $user->email }}</option>@endforeach
                    </select>
                    <select name="subject_id" x-show="scope === 'role'" :disabled="scope !== 'role'" required class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-[#E6EEF8]">
                        @foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                    </select>
                    <select name="subject_id" x-show="scope === 'department'" :disabled="scope !== 'department'" required class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-[#E6EEF8]">
                        @foreach ($departments as $department)<option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-[#9FB3C8]">Une affectation individuelle remplace celles du rôle et de la structure.</p>
                </div>
                <div>
                    <label class="dgcpt-card-title mb-2 block" for="expires_at">Expiration facultative</label>
                    <input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}" class="block w-full rounded-lg border border-[rgba(0,209,255,0.22)] bg-[#050816] px-3 py-2 text-[#E6EEF8]">
                </div>
            </div>

            <fieldset>
                <legend class="dgcpt-card-title mb-3">Menus disponibles dans l’APK</legend>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($catalog as $key => $menu)
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-[rgba(0,209,255,0.18)] bg-[#081124] p-4 hover:border-[#00D1FF]/50">
                            <input type="checkbox" name="menus[]" value="{{ $key }}" @checked(in_array($key, old('menus', []), true)) class="mt-1 rounded border-[#00D1FF]/40 bg-[#050816] text-[#00D1FF] focus:ring-[#00D1FF]">
                            <span><strong class="block text-[#E6EEF8]">{{ $menu['label'] }}</strong><span class="mt-1 block text-xs text-[#9FB3C8]">{{ $menu['description'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit" class="rounded-xl bg-gradient-to-r from-[#0A2A66] to-blue-950 px-5 py-3 text-sm font-bold uppercase tracking-wider text-white ring-1 ring-[rgba(0,209,255,0.3)]">Enregistrer l’habilitation mobile</button>
        </form>

        <div class="dgcpt-surface overflow-x-auto p-5">
            <h2 class="mb-4 text-lg font-bold text-[#E6EEF8]">Affectations actives</h2>
            <table class="dgcpt-table min-w-full">
                <thead><tr><th>Cible</th><th>Type</th><th>Menus</th><th>Expiration</th><th>Action</th></tr></thead>
                <tbody>
                @forelse ($assignments as $subjectKey => $rows)
                    @php [$type, $id] = explode(':', $subjectKey, 2); $first = $rows->first(); @endphp
                    <tr>
                        <td class="font-semibold text-[#E6EEF8]">{{ data_get($subjectLabels, $type.'.'.$id, 'Cible #'.$id) }}</td>
                        <td class="text-[#9FB3C8]">{{ ['user' => 'Personne', 'role' => 'Rôle', 'department' => 'Structure'][$type] ?? $type }}</td>
                        <td><div class="flex max-w-xl flex-wrap gap-1">@foreach ($rows as $row)<span class="rounded-full bg-[#0A2A66] px-2 py-1 text-xs text-[#E6EEF8]">{{ $catalog[$row->menu_key]['label'] ?? $row->menu_key }}</span>@endforeach</div></td>
                        <td class="whitespace-nowrap text-[#9FB3C8]">{{ $first->expires_at?->format('d/m/Y H:i') ?? 'Sans expiration' }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.mobile-menu.destroy', [$type, $id]) }}" onsubmit="return confirm('Supprimer cette habilitation mobile ?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-[#FF8A8A] hover:underline">Supprimer</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-5 text-center text-[#9FB3C8]">Aucune affectation mobile configurée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
