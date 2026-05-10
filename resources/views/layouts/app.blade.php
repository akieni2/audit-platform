<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Audit Platform') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: linear-gradient(165deg, #0f172a 0%, #1e293b 48%, #172554 100%);
            --sidebar-border: rgba(148, 163, 184, 0.12);
            --sidebar-text: #e2e8f0;
            --sidebar-muted: #94a3b8;
            --accent: #6366f1;
            --accent-soft: rgba(99, 102, 241, 0.18);
            --success: #10b981;
            --success-soft: rgba(16, 185, 129, 0.15);
            --danger: #ef4444;
            --main-bg: #f8fafc;
            --card-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(15, 23, 42, 0.04);
            --radius: 10px;
            --sidebar-width: 288px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            font-size: 15px;
            color: #0f172a;
            background: var(--main-bg);
        }

        .app-shell {
            display: flex;
            min-height: 100vh;
            align-items: stretch;
        }

        /* Sidebar : défilante — la section IAM reste accessible même avec beaucoup de pôles */
        .sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.12);
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 6px;
        }

        .sidebar-inner {
            padding: 1.25rem 1rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .brand {
            padding: 0.35rem 0.5rem 0.15rem;
            border-bottom: 1px solid var(--sidebar-border);
            margin-bottom: 0.25rem;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #fff;
            margin: 0;
            line-height: 1.3;
        }

        .brand-sub {
            font-size: 0.72rem;
            color: var(--sidebar-muted);
            margin: 0.25rem 0 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .nav-section-title {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--sidebar-muted);
            margin: 0.75rem 0.5rem 0.35rem;
        }

        .nav-section-title:first-of-type { margin-top: 0; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 0.65rem;
            margin: 0.15rem 0;
            border-radius: var(--radius);
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: background 0.15s ease, color 0.15s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
        }

        .nav-link.active {
            background: var(--accent-soft);
            border-color: rgba(99, 102, 241, 0.35);
            color: #fff;
        }

        .nav-link .ni {
            font-size: 1rem;
            opacity: 0.88;
            width: 1.25rem;
            text-align: center;
        }

        /* Bloc institutionnel ADMINISTRATION — distinct des modules métiers */
        .nav-card--admin {
            border-radius: var(--radius);
            padding: 0.75rem 0.55rem 0.65rem;
            margin: 0.5rem 0 0.85rem;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(15, 23, 42, 0.55);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .nav-card--admin .nav-card-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #cbd5e1;
            margin: 0 0.45rem 0.65rem;
            padding-bottom: 0.45rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }

        .nav-card--admin .nav-link {
            margin: 0.18rem 0;
            font-size: 0.87rem;
            font-weight: 500;
        }

        .nav-card--admin .nav-link:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(148, 163, 184, 0.12);
        }

        .nav-card--admin .nav-link.active {
            background: rgba(99, 102, 241, 0.22);
            border-color: rgba(129, 140, 248, 0.45);
            box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.15);
        }

        .nav-card--admin .nav-link--emphasis {
            font-weight: 600;
            border-color: rgba(148, 163, 184, 0.18);
            background: rgba(30, 41, 59, 0.45);
        }

        .nav-card--admin .nav-link--emphasis:hover {
            background: rgba(51, 65, 85, 0.55);
        }

        .dept-pill {
            font-size: 0.78rem;
            font-weight: 500;
            line-height: 1.35;
        }

        .main-wrap {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--main-bg);
        }

        .content {
            flex: 1;
            padding: 1.35rem 1.75rem 2.5rem;
            max-width: 100%;
        }

        .app-topbar {
            background: linear-gradient(105deg, #1e293b 0%, #312e81 55%, #1e3a5f 100%);
            color: #fff;
            padding: 1rem 1.35rem;
            margin: -1.35rem -1.75rem 1.35rem -1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 0.85rem;
            border-bottom: 3px solid var(--accent);
            box-shadow: var(--card-shadow);
        }

        .app-topbar .welcome-badge {
            display: inline-block;
            background: var(--success);
            color: #042f2e;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            margin-right: 0.65rem;
            font-weight: 700;
        }

        .btn-logout {
            width: 100%;
            margin-top: 0.35rem;
            padding: 0.55rem 0.65rem;
            border-radius: var(--radius);
            border: 1px solid rgba(248, 113, 113, 0.45);
            background: rgba(127, 29, 29, 0.35);
            color: #fecaca;
            font-family: inherit;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-logout:hover {
            background: rgba(185, 28, 28, 0.55);
            color: #fff;
        }

        @media (max-width: 960px) {
            .app-shell { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                max-height: min(68vh, 28rem);
                position: relative;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" aria-label="Navigation principale">
        <div class="sidebar-inner">
            <div class="brand">
                <p class="brand-title">Audit Platform</p>
                <p class="brand-sub">DGCPT · Pilotage</p>
            </div>

            <nav>
                <p class="nav-section-title">Accueil</p>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <span class="ni" aria-hidden="true">◆</span>
                    Tableau de bord
                </a>

                @auth
                @php
                    auth()->user()->loadMissing(['institutionalRole', 'institutionalRole.permissions']);
                @endphp
                @if(auth()->user()->canAccessAdministrationMenu())
                    <div class="nav-card nav-card--admin" role="navigation" aria-label="Administration">
                        <p class="nav-card-title">Administration</p>
                        <a class="nav-link {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.edit') ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}">
                            <span class="ni" aria-hidden="true">▣</span>
                            Utilisateurs
                        </a>
                        <a class="nav-link nav-link--emphasis {{ request()->routeIs('admin.users.create') ? 'active' : '' }}"
                           href="{{ route('admin.users.create') }}">
                            <span class="ni" aria-hidden="true">◇</span>
                            Créer utilisateur
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}"
                           href="{{ route('admin.home') }}">
                            <span class="ni" aria-hidden="true">◎</span>
                            Tableau de bord admin
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.security.audit-logs') ? 'active' : '' }}"
                           href="{{ route('admin.security.audit-logs') }}">
                            <span class="ni" aria-hidden="true">▤</span>
                            Journal sécurité
                        </a>
                    </div>
                @endif
                @endauth

                @can('viewExecutiveDashboard')
                    <a class="nav-link {{ request()->routeIs('dashboard.executive') ? 'active' : '' }}"
                       href="{{ route('dashboard.executive') }}">
                        <span class="ni" aria-hidden="true">◈</span>
                        Tableau exécutif
                    </a>
                @endcan

                <p class="nav-section-title">Missions</p>
                <a class="nav-link {{ request()->routeIs('missions.index') ? 'active' : '' }}"
                   href="{{ route('missions.index') }}">
                    <span class="ni" aria-hidden="true">≡</span>
                    Liste des missions
                </a>
                <a class="nav-link {{ request()->routeIs('missions.create') ? 'active' : '' }}"
                   href="{{ route('missions.create') }}">
                    <span class="ni" aria-hidden="true">＋</span>
                    Nouvelle mission
                </a>

                <p class="nav-section-title">Audit</p>
                <a class="nav-link {{ request()->routeIs('missions.index') ? 'active' : '' }}"
                   href="{{ route('missions.index') }}">
                    <span class="ni" aria-hidden="true">◊</span>
                    Services audités
                </a>
                <a class="nav-link {{ request()->routeIs('cartographie.*') ? 'active' : '' }}"
                   href="{{ route('cartographie.select') }}">
                    <span class="ni" aria-hidden="true">◐</span>
                    Cartographie des risques
                </a>

                <p class="nav-section-title">Analyse</p>
                <a class="nav-link {{ request()->routeIs('module.entretiens') ? 'active' : '' }}"
                   href="{{ route('module.entretiens') }}">
                    <span class="ni" aria-hidden="true">○</span>
                    Entretiens
                </a>
                <a class="nav-link {{ request()->routeIs('module.processus') ? 'active' : '' }}"
                   href="{{ route('module.processus') }}">
                    <span class="ni" aria-hidden="true">↗</span>
                    Processus
                </a>
                <a class="nav-link {{ request()->routeIs('module.actifs') ? 'active' : '' }}"
                   href="{{ route('module.actifs') }}">
                    <span class="ni" aria-hidden="true">▦</span>
                    Actifs
                </a>
                <a class="nav-link {{ request()->routeIs('module.risques') ? 'active' : '' }}"
                   href="{{ route('module.risques') }}">
                    <span class="ni" aria-hidden="true">※</span>
                    Risques
                </a>

                <p class="nav-section-title">Suivi</p>
                <a class="nav-link {{ request()->routeIs('module.actions') ? 'active' : '' }}"
                   href="{{ route('module.actions') }}">
                    <span class="ni" aria-hidden="true">✓</span>
                    Actions correctives
                </a>
                <a class="nav-link {{ request()->routeIs('module.rapports') ? 'active' : '' }}"
                   href="{{ route('module.rapports') }}">
                    <span class="ni" aria-hidden="true">▤</span>
                    Rapports
                </a>

                @if(isset($sidebarDepartments) && $sidebarDepartments->isNotEmpty())
                    <p class="nav-section-title">Pôles / départements</p>
                    @foreach($sidebarDepartments as $dept)
                        <a class="nav-link dept-pill {{ (string) request()->query('department') === (string) $dept->id ? 'active' : '' }}"
                           href="{{ route('missions.index', ['department' => $dept->id]) }}">
                            <span class="ni" aria-hidden="true">▸</span>
                            <span><strong>{{ $dept->code }}</strong> — {{ \Illuminate\Support\Str::limit($dept->name, 26) }}</span>
                        </a>
                    @endforeach
                    <a class="nav-link" href="{{ route('missions.index') }}" style="opacity:.88;font-size:0.82rem;">
                        <span class="ni" aria-hidden="true">∞</span>
                        Toutes les missions
                    </a>
                @endif

                @auth
                    <p class="nav-section-title">Compte</p>
                    <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}">
                        <span class="ni" aria-hidden="true">●</span>
                        Mon profil
                    </a>
                    <a class="nav-link {{ request()->routeIs('profile.security') ? 'active' : '' }}"
                       href="{{ route('profile.security') }}">
                        <span class="ni" aria-hidden="true">⌗</span>
                        Sécurité
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">Déconnexion</button>
                    </form>
                @endauth
            </nav>
        </div>
    </aside>

    <div class="main-wrap">
        <div class="content">
            @auth
                @php
                    auth()->user()->loadMissing('department', 'institutionalRole');
                @endphp
                <div class="app-topbar">
                    <div>
                        @if(session('welcome_once'))
                            <span class="welcome-badge">{{ session('welcome_once') }}</span>
                        @endif
                        <span style="font-size:1.02rem;"><strong>{{ auth()->user()->displayName() }}</strong></span>
                        @if(auth()->user()->institutionalRole)
                            <span style="opacity:.88;font-size:0.82rem;margin-left:0.5rem;">{{ auth()->user()->institutionalRole->name }}</span>
                        @endif
                    </div>
                    <div style="text-align:right;max-width:32rem;">
                        @if(auth()->user()->department)
                            <div style="font-size:0.72rem;opacity:.82;text-transform:uppercase;letter-spacing:.06em;">Pôle / département</div>
                            <div style="font-size:0.95rem;margin-top:0.25rem;">
                                <strong>{{ auth()->user()->department->code }}</strong>
                                <span style="opacity:.92;"> — {{ auth()->user()->department->name }}</span>
                            </div>
                        @else
                            <div style="font-size:0.82rem;opacity:.78;">Aucun département affecté — contactez l’administration.</div>
                        @endif
                    </div>
                </div>
            @endauth

            {{ $slot }}
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')
</body>
</html>
