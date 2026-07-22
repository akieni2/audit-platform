<x-app-layout>

<div style="max-width:720px;">
    <h2 style="margin-bottom:8px;">{{ $title }}</h2>
    <p style="color:#475569;margin-bottom:16px;">{{ $intro }}</p>

    <p style="margin-bottom:20px;">
        <a href="{{ $missionsIndexUrl }}" style="color:#2563eb;">← Retour aux missions</a>
    </p>

    <table style="width:100%;border-collapse:collapse;background:white;box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <thead>
            <tr style="background:#1e293b;color:white;">
                <th style="text-align:left;padding:10px;">Mission</th>
                <th style="text-align:left;padding:10px;">Accès</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $row)
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px;">
                        <strong>{{ $row['mission']->organisation }}</strong>
                        <div style="font-size:12px;color:#64748b;">{{ $row['mission']->date_debut }}</div>
                    </td>
                    <td style="padding:10px;">
                        @if($row['ready'] && $row['url'])
                            <a href="{{ $row['url'] }}" style="display:inline-block;background:#2563eb;color:white;padding:6px 12px;border-radius:4px;text-decoration:none;font-size:14px;">
                                Ouvrir
                            </a>
                        @else
                            <span style="color:#94a3b8;font-size:14px;">—</span>
                            @if(!empty($row['hint']))
                                <div style="font-size:12px;color:#b45309;margin-top:4px;">{{ $row['hint'] }}</div>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="padding:16px;text-align:center;color:#64748b;">
                        Aucune mission.
                        @can('create', \App\Models\Mission::class)
                            <a href="{{ route('missions.create') }}">Créer une mission</a>
                        @endcan
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-app-layout>
