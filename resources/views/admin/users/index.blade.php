<x-app-layout>

<div style="max-width:960px;">
    <h2 style="margin-bottom:8px;">Administration — rôles utilisateurs</h2>
    <p style="color:#475569;margin-bottom:20px;font-size:14px;">
        Seuls les administrateurs accèdent à cette page. Attribuez le rôle <strong>Risk Manager</strong> pour autoriser la modification des risques critiques (avec les administrateurs).
    </p>

    @if (session('status'))
        <p style="margin-bottom:16px;padding:10px 12px;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:6px;color:#065f46;font-size:14px;">
            {{ session('status') }}
        </p>
    @endif

    @if ($errors->any())
        <div style="margin-bottom:16px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:14px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background:white;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#1e293b;color:white;">
                    <th style="text-align:left;padding:12px;">Nom</th>
                    <th style="text-align:left;padding:12px;">Email</th>
                    <th style="text-align:left;padding:12px;">Rôle</th>
                    <th style="padding:12px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:12px;">{{ $u->name }}</td>
                        <td style="padding:12px;">{{ $u->email }}</td>
                        <td style="padding:12px;">
                            <form method="POST" action="{{ route('admin.users.role.update', $u) }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                @csrf
                                @method('PATCH')
                                <select name="role" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:4px;min-width:160px;">
                                    @foreach ($roleOptions as $value)
                                        <option value="{{ $value }}" @selected($u->role === $value)>
                                            {{ \App\Support\UserRoles::label($value) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" style="background:#2563eb;color:white;border:none;padding:6px 14px;border-radius:4px;cursor:pointer;font-size:13px;">
                                    Enregistrer
                                </button>
                            </form>
                        </td>
                        <td style="padding:12px;font-size:12px;color:#64748b;">
                            @if(auth()->id() === $u->id)
                                (vous)
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $users->links() }}
    </div>

    <p style="margin-top:24px;font-size:13px;color:#64748b;">
        Documentation API (OpenAPI 3) : <a href="{{ url('/openapi.yaml') }}" style="color:#2563eb;">{{ url('/openapi.yaml') }}</a>
        — import possible dans Postman (Import → fichier ou URL).
    </p>
</div>

</x-app-layout>
