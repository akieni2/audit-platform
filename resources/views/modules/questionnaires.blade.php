<x-app-layout>

<div style="max-width:640px;">
    <h2>Questionnaires</h2>
    <p style="color:#475569;margin-bottom:16px;">
        Liste des questionnaires en base. L’association aux entretiens peut être étendue ultérieurement (écran d’édition dédié).
    </p>
    <p style="margin-bottom:20px;">
        <a href="{{ route('module.entretiens') }}" style="color:#2563eb;">← Entretiens par mission</a>
    </p>

    @if($items->isEmpty())
        <p style="color:#64748b;">Aucun questionnaire enregistré.</p>
    @else
        <ul style="background:white;padding:16px 24px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
            @foreach($items as $q)
                <li style="margin-bottom:12px;">
                    <strong>{{ $q->titre }}</strong>
                    <span style="color:#64748b;font-size:14px;">— {{ $q->questions_count }} question(s)</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

</x-app-layout>
