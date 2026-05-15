<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 px-0 py-2">
        @if (session('status'))
            <div class="dgcpt-surface border-[#00A86B]/35 px-4 py-3 text-sm text-[#E6EEF8] ring-1 ring-[rgba(0,168,107,0.25)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dgcpt-card-title">RACI Designer</p>
                <h1 class="dgcpt-page-title">{{ $template->name }}</h1>
                <p class="mt-1 text-sm text-[#9FB3C8]">{{ $template->department?->code ?? 'National' }} · {{ $template->analysis_scope }} · v{{ $template->version }}</p>
            </div>
            <a href="{{ route('raci-builder.index') }}" class="dgcpt-btn-outline">Retour bibliotheque</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
            <div class="space-y-6">
                <div class="dgcpt-surface p-6 shadow-sm">
                    <p class="dgcpt-card-title">Matrice interactive</p>
                    <div class="mt-5 overflow-x-auto">
                        <table class="dgcpt-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Processus</th>
                                    @foreach ($builder['roles'] as $role)
                                        <th class="text-left">{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($builder['matrix'] as $row)
                                    <tr>
                                        <td class="font-semibold text-[#E6EEF8]">{{ $row['process_label'] }}</td>
                                        @foreach ($row['cells'] as $cell)
                                            <td>
                                                @if ($cell['assignment'])
                                                    <span class="rounded-full bg-[rgba(0,209,255,0.08)] px-2.5 py-1 text-xs font-semibold text-[#73D8FF]">
                                                        {{ strtoupper(substr((string) $cell['assignment']->role_type, 0, 1)) }}
                                                    </span>
                                                @else
                                                    <span class="text-[#6D7B90]">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="dgcpt-surface p-6 shadow-sm">
                        <p class="dgcpt-card-title">Ajouter un role</p>
                        <form method="POST" action="{{ route('raci-builder.roles.store', $template) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="dgcpt-label">Nom</label>
                                <input name="name" type="text" required class="dgcpt-input" />
                            </div>
                            <div>
                                <label class="dgcpt-label">Code</label>
                                <input name="code" type="text" class="dgcpt-input" />
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Type</label>
                                    <select name="role_type" class="dgcpt-input">
                                        @foreach ($roleTypeLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="dgcpt-label">Niveau</label>
                                    <select name="responsibility_level" class="dgcpt-input">
                                        @foreach ($responsibilityLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="dgcpt-btn-primary">Ajouter role</button>
                        </form>
                    </div>

                    <div class="dgcpt-surface p-6 shadow-sm">
                        <p class="dgcpt-card-title">Ajouter un lien</p>
                        <form method="POST" action="{{ route('raci-builder.assignments.store', $template) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <label class="dgcpt-label">Role</label>
                                <select name="raci_role_id" class="dgcpt-input">
                                    @foreach ($template->roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="dgcpt-label">Processus</label>
                                <input name="process_label" type="text" required class="dgcpt-input" />
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="dgcpt-label">Type</label>
                                    <select name="role_type" class="dgcpt-input">
                                        @foreach ($roleTypeLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="dgcpt-label">Niveau</label>
                                    <select name="responsibility_level" class="dgcpt-input">
                                        @foreach ($responsibilityLabels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="dgcpt-label">Notes</label>
                                <textarea name="notes" rows="3" class="dgcpt-textarea"></textarea>
                            </div>
                            <button type="submit" class="dgcpt-btn-primary">Ajouter affectation</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="dgcpt-surface p-6 shadow-sm">
                <p class="dgcpt-card-title">Configuration template</p>
                <form method="POST" action="{{ route('raci-builder.update', $template) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="dgcpt-label">Nom</label>
                        <input name="name" type="text" value="{{ $template->name }}" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Slug</label>
                        <input name="slug" type="text" value="{{ $template->slug }}" required class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Code</label>
                        <input name="code" type="text" value="{{ $template->code }}" class="dgcpt-input" />
                    </div>
                    <div>
                        <label class="dgcpt-label">Departement</label>
                        <select name="department_id" class="dgcpt-input">
                            <option value="">National</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}" @selected((int) $template->department_id === (int) $department->id)>{{ $department->code }} - {{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="dgcpt-label">Description</label>
                        <textarea name="description" rows="4" class="dgcpt-textarea">{{ $template->description }}</textarea>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="dgcpt-label">Scope</label>
                            <select name="analysis_scope" class="dgcpt-input">
                                @foreach (['mission' => 'Mission', 'department' => 'Departement', 'national' => 'National'] as $value => $label)
                                    <option value="{{ $value }}" @selected($template->analysis_scope === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2 pt-6 text-sm text-[#BFD2E6]">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="active" value="1" @checked($template->active) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Actif
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="is_global" value="1" @checked($template->is_global) class="rounded border-[rgba(0,209,255,0.2)] bg-[#050816]" />
                                Global
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="dgcpt-btn-primary">Mettre a jour</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
