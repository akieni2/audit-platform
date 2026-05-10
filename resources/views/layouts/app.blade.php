<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Audit Platform') }}</title>
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

        [x-cloak] { display: none !important; }

        html.dark body {
            background: #020617;
            color: #e2e8f0;
        }

        html.dark .main-wrap {
            background: #020617;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            font-size: 15px;
            color: #0f172a;
            background: var(--main-bg);
        }

        /* flex-start : évite que la colonne latérale s’étire sur toute la hauteur du document (effets de superposition / hit-area avec sticky) */
        .app-shell {
            display: flex;
            min-height: 100vh;
            align-items: flex-start;
            position: relative;
        }

        /* Sidebar : défilante — la section IAM reste accessible même avec beaucoup de pôles */
        .sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            align-self: flex-start;
            height: 100vh;
            max-height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.12);
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
            z-index: 1;
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
            width: 100%;
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

        .nav-badge {
            margin-left: auto;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.4rem;
            border-radius: 999px;
            background: var(--danger);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .topbar-notif {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            margin-right: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: background 0.15s ease;
        }

        .topbar-notif:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .topbar-notif-count {
            background: var(--danger);
            color: #fff;
            border-radius: 999px;
            font-size: 0.68rem;
            min-width: 1.25rem;
            height: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.35rem;
            font-weight: 700;
        }

        .main-wrap {
            flex: 1 1 0;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: var(--main-bg);
            position: relative;
            z-index: 2;
            isolation: isolate;
        }

        .content {
            flex: 1;
            padding: 1.35rem 1.75rem 2.5rem;
            max-width: 100%;
            position: relative;
            pointer-events: auto;
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

        .mobile-nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            margin-right: 0.35rem;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 1.15rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .mobile-nav-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            margin-left: 0.35rem;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex: 1;
            min-width: 0;
            max-width: 22rem;
        }

        .topbar-search input {
            flex: 1;
            min-width: 0;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(15, 23, 42, 0.35);
            color: #fff;
            padding: 0.45rem 0.65rem;
            font-family: inherit;
            font-size: 0.82rem;
        }

        .topbar-search input::placeholder {
            color: rgba(255, 255, 255, 0.55);
        }

        @media (max-width: 960px) {
            .mobile-nav-toggle {
                display: inline-flex;
            }

            .topbar-search {
                display: none;
            }

            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.55);
                z-index: 38;
            }

            .app-shell {
                flex-direction: row;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform 0.28s ease;
                height: 100vh;
                max-height: 100vh;
                width: min(var(--sidebar-width), 92vw);
            }

            .sidebar.sidebar--open {
                transform: translateX(0);
            }

            .main-wrap {
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased">
<div class="app-shell" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <div class="sidebar-backdrop"
         x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         x-cloak></div>

    <aside class="sidebar"
           aria-label="Navigation principale"
           x-bind:class="{ 'sidebar--open': sidebarOpen }">
        <div class="sidebar-inner">
            <div class="brand">
                <p class="brand-title">Audit Platform</p>
                <p class="brand-sub">DGCPT · Pilotage</p>
            </div>

            <nav>
                @include('layouts.partials.sidebar-navigation')
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
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;flex:1;min-width:0;">
                        <button type="button" class="mobile-nav-toggle" @click="sidebarOpen = true" aria-label="Ouvrir le menu">☰</button>
                        @if(session('welcome_once'))
                            <span class="welcome-badge">{{ session('welcome_once') }}</span>
                        @endif
                        <form method="get" action="{{ route('search') }}" class="topbar-search" role="search">
                            <label class="sr-only" for="topbar-search-q">Recherche globale</label>
                            <input id="topbar-search-q" type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Recherche missions…" autocomplete="off" />
                            <button type="submit" class="theme-toggle" style="width:auto;padding:0 0.65rem;font-size:0.78rem;font-weight:600;">OK</button>
                        </form>
                        <a href="{{ route('notifications.index') }}" class="topbar-notif" aria-label="Centre de notifications">
                            Notifications
                            <span data-notif-count class="topbar-notif-count"
                                  style="{{ ($unreadNotificationsCount ?? 0) > 0 ? '' : 'display:none;' }}"
                                  aria-hidden="true">{{ ($unreadNotificationsCount ?? 0) > 99 ? '99+' : ($unreadNotificationsCount ?? 0) }}</span>
                        </a>
                        <button type="button" class="theme-toggle" onclick="window.toggleInstitutionalTheme()" title="Thème clair / sombre">◐</button>
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
@auth
    <script>
        window.__auditUserId = {{ auth()->id() }};
        window.toggleInstitutionalTheme = function () {
            document.documentElement.classList.toggle('dark');
            try {
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            } catch (e) {}
        };
        window.refreshAuditNotifications = function () {
            var url = @json(route('notifications.unread-count'));
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var n = typeof d.count === 'number' ? d.count : 0;
                    document.querySelectorAll('[data-notif-count]').forEach(function (el) {
                        el.textContent = n > 99 ? '99+' : String(n);
                        el.style.display = n > 0 ? '' : 'none';
                    });
                })
                .catch(function () {});
        };
        setInterval(window.refreshAuditNotifications, 45000);
    </script>
@endauth
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
@stack('scripts')
</body>
</html>
