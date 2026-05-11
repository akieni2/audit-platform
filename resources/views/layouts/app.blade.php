<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DGCPT') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/dgcpt-logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/branding/dgcpt-logo.png') }}">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dgcpt-dashboard.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <div class="brand-mark">
                    <img src="{{ asset('assets/branding/dgcpt-logo.png') }}" width="48" height="48" alt="DGCPT">
                    <div>
                        <p class="brand-title">DGCPT</p>
                        <p class="brand-sub">Trésor public gabonais</p>
                    </div>
                </div>
                <p class="brand-sub" style="margin-top:0.65rem;letter-spacing:0.04em;">Plateforme d’audit &amp; pilotage</p>
            </div>

            <nav class="sidebar-nav">
                @include('layouts.partials.sidebar-navigation')
            </nav>

            @auth
                <div class="sidebar-status" role="status">
                    <p class="sidebar-status-label">Statut système</p>
                    <div class="sidebar-status-row">
                        <span class="sidebar-status-dot" aria-hidden="true"></span>
                        <span>Opérationnel</span>
                    </div>
                </div>
            @endauth
        </div>
    </aside>

    <div class="main-wrap">
        <div class="content">
            @auth
                @php
                    auth()->user()->loadMissing('department', 'institutionalRole');
                @endphp
                <div class="app-topbar">
                    <div class="app-topbar-left">
                        <button type="button" class="mobile-nav-toggle" @click="sidebarOpen = true" aria-label="Ouvrir le menu">☰</button>
                        @if(session('welcome_once'))
                            <span class="welcome-badge">{{ session('welcome_once') }}</span>
                        @endif
                        <form method="get" action="{{ route('search') }}" class="topbar-search" role="search">
                            <label class="sr-only" for="topbar-search-q">Recherche globale</label>
                            <input id="topbar-search-q" type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Recherche missions…" autocomplete="off" />
                            <button type="submit" class="theme-toggle topbar-search-submit" aria-label="Lancer la recherche">OK</button>
                        </form>
                        <a href="{{ route('notifications.index') }}" class="topbar-notif" aria-label="Centre de notifications">
                            Notifications
                            <span data-notif-count class="topbar-notif-count"
                                  style="{{ ($unreadNotificationsCount ?? 0) > 0 ? '' : 'display:none;' }}"
                                  aria-hidden="true">{{ ($unreadNotificationsCount ?? 0) > 99 ? '99+' : ($unreadNotificationsCount ?? 0) }}</span>
                        </a>
                        <button type="button" class="theme-toggle" onclick="window.toggleInstitutionalTheme()" title="Thème clair / sombre">◐</button>
                        <span class="app-topbar-user"><strong>{{ auth()->user()->displayName() }}</strong></span>
                        @if(auth()->user()->institutionalRole)
                            <span class="app-topbar-role">{{ auth()->user()->institutionalRole->name }}</span>
                        @endif
                    </div>
                    <div class="app-topbar-dept">
                        @if(auth()->user()->department)
                            <div class="app-topbar-dept-label">Pôle / département</div>
                            <div class="app-topbar-dept-value">
                                <strong>{{ auth()->user()->department->code }}</strong>
                                <span> — {{ auth()->user()->department->name }}</span>
                            </div>
                        @else
                            <div class="app-topbar-dept-empty">Aucun département affecté — contactez l’administration.</div>
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
