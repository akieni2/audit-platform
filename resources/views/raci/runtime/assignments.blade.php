<div class="dgcpt-surface p-6 shadow-sm">
    <p class="dgcpt-card-title">Nouvelle affectation</p>
    <form method="POST" action="{{ route('raci.assignments', $mission) }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @csrf
        <div>
            <label class="dgcpt-label">Template RACI</label>
            <select name="raci_template_id" class="dgcpt-input">
                @foreach ($raciTemplates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Role</label>
            <select name="raci_role_id" class="dgcpt-input">
                @foreach ($roleOptions as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Utilisateur</label>
            <select name="assigned_user_id" class="dgcpt-input">
                <option value="">Aucun</option>
                @foreach ($userOptions as $user)
                    <option value="{{ $user->id }}">{{ $user->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Processus</label>
            <input name="process_label" type="text" required class="dgcpt-input" />
        </div>
        <div>
            <label class="dgcpt-label">Type</label>
            <select name="role_type" class="dgcpt-input">
                <option value="responsible">Responsible</option>
                <option value="accountable">Responsable final</option>
                <option value="consulted">Consulted</option>
                <option value="informed">Informed</option>
            </select>
        </div>
        <div>
            <label class="dgcpt-label">Niveau</label>
            <select name="responsibility_level" class="dgcpt-input">
                <option value="low">Faible</option>
                <option value="moderate">Modere</option>
                <option value="high">Eleve</option>
                <option value="critical">Critique</option>
            </select>
        </div>
        <div class="md:col-span-2 xl:col-span-3">
            <label class="dgcpt-label">Notes</label>
            <textarea name="notes" rows="3" class="dgcpt-textarea"></textarea>
        </div>
        <div class="md:col-span-2 xl:col-span-3">
            <button type="submit" class="dgcpt-btn-primary">Affecter</button>
        </div>
    </form>
</div>
