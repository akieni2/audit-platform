<x-app-layout>

<div style="max-width:640px;">
    <h2>Questionnaires</h2>
    <p style="color:#475569;margin-bottom:16px;">
        Le référentiel dynamique est désormais la source canonique. Le legacy reste visible en lecture seule pendant la convergence.
    </p>
    <p style="margin-bottom:20px;">
        <a href="{{ route('module.entretiens') }}" style="color:#2563eb;">← Entretiens par mission</a>
        <span style="color:#94a3b8;"> · </span>
        <a href="{{ route('questionnaire-builder.index') }}" style="color:#2563eb;">Builder officiel</a>
    </p>

    <h3 style="margin-bottom:10px;">Bibliothèque dynamique</h3>
    @if($templates->isEmpty())
        <p style="color:#64748b;">Aucun modèle dynamique enregistré.</p>
    @else
        <ul style="background:white;padding:16px 24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
            @foreach($templates as $template)
                <li style="margin-bottom:12px;">
                    <strong>{{ $template->name }}</strong>
                    <span style="color:#64748b;font-size:14px;">
                        — {{ $template->sections->count() }} section(s), {{ $template->sections->sum(fn ($section) => $section->questions->count()) }} question(s)
                    </span>
                </li>
            @endforeach
        </ul>
    @endif

    <h3 style="margin:24px 0 10px;">Archive legacy (lecture seule)</h3>
    @if($legacyItems->isEmpty())
        <p style="color:#64748b;">Aucun questionnaire legacy enregistré.</p>
    @else
        <ul style="background:white;padding:16px 24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
            @foreach($legacyItems as $q)
                <li style="margin-bottom:12px;">
                    <strong>{{ $q->titre }}</strong>
                    <span style="color:#64748b;font-size:14px;">— {{ $q->questions_count }} question(s)</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

</x-app-layout>
