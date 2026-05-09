<x-app-layout>

<div style="max-width:960px;">
    <h2 style="margin-bottom:8px;">Tableau de bord exécutif — Inspection des Services</h2>
    <p style="color:#475569;margin-bottom:24px;font-size:14px;">
        Vue consolidée (missions, risques critiques et risques transversaux). Les filtres par pôle et exports seront étendus dans les prochaines itérations.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        @foreach($kpis as $label => $value)
            <div style="background:white;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <div style="font-size:13px;color:#64748b;text-transform:capitalize;">
                    {{ str_replace('_', ' ', $label) }}
                </div>
                <div style="font-size:28px;font-weight:700;color:#0f172a;">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <p style="margin-top:24px;font-size:13px;color:#64748b;">
        <a href="{{ route('dashboard') }}" style="color:#2563eb;">← Tableau de bord utilisateur</a>
    </p>
</div>

</x-app-layout>
