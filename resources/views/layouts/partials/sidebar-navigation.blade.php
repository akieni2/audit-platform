@php
    $nav = $institutionalNavMode ?? 'department';
@endphp

@auth
    @if ($nav === 'technical_admin')
        @if (($canManageUsers ?? false) || ($canManageDepartmentsNav ?? false))
            <div class="nav-card nav-card--admin" role="navigation" aria-label="Administration technique">
                <p class="nav-card-title">Administration</p>
                @if ($canManageUsers ?? false)
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
                        Console administration
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.security.audit-logs') ? 'active' : '' }}"
                       href="{{ route('admin.security.audit-logs') }}">
                        <span class="ni" aria-hidden="true">▤</span>
                        Journal sécurité
                    </a>
                @endif
                @if ($canManageDepartmentsNav ?? false)
                    <a class="nav-link {{ request()->routeIs(['admin.departments.index', 'admin.departments.create', 'admin.departments.edit']) ? 'active' : '' }}"
                       href="{{ route('admin.departments.index') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        Pôles / départements
                    </a>
                @endif
            </div>
        @endif

    @elseif ($nav === 'copri')
        <p class="nav-section-title">Pilotage COPRI</p>
        @can('viewExecutiveDashboard')
            <a class="nav-link {{ request()->routeIs('dashboard.executive') ? 'active' : '' }}"
               href="{{ route('dashboard.executive') }}">
                <span class="ni" aria-hidden="true">◈</span>
                Tableau stratégique national
            </a>
        @endcan

    @else
        {{-- Inspection des Services — consolidation / validation — ou utilisateur département (workflow ascendant) --}}
        <p class="nav-section-title">Accueil</p>
        <a class="nav-link {{ request()->routeIs('dashboard') && ! request()->routeIs('dashboard.executive') ? 'active' : '' }}"
           href="{{ route('dashboard') }}">
            <span class="ni" aria-hidden="true">◆</span>
            Tableau de bord
        </a>

        @if (($canManageUsers ?? false) || ($canManageDepartmentsNav ?? false))
            <div class="nav-card nav-card--admin" role="navigation" aria-label="Administration">
                <p class="nav-card-title">Administration</p>
                @if ($canManageUsers ?? false)
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
                @endif
                @if ($canManageDepartmentsNav ?? false)
                    <a class="nav-link {{ request()->routeIs(['admin.departments.index', 'admin.departments.create', 'admin.departments.edit']) ? 'active' : '' }}"
                       href="{{ route('admin.departments.index') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        Pôles / départements
                    </a>
                @endif
            </div>
        @endif

        @can('viewExecutiveDashboard')
            <a class="nav-link {{ request()->routeIs('dashboard.executive') ? 'active' : '' }}"
               href="{{ route('dashboard.executive') }}">
                <span class="ni" aria-hidden="true">◈</span>
                @if ($nav === 'inspection')
                    Consolidation & validation (IS)
                @else
                    Vue nationale
                @endif
            </a>
        @endcan

        <p class="nav-section-title">Missions</p>
        <a class="nav-link {{ request()->routeIs('missions.index') ? 'active' : '' }}"
           href="{{ route('missions.index') }}">
            <span class="ni" aria-hidden="true">≡</span>
            Missions
        </a>
        <a class="nav-link {{ request()->routeIs('missions.create') ? 'active' : '' }}"
           href="{{ route('missions.create') }}">
            <span class="ni" aria-hidden="true">＋</span>
            Nouvelle mission
        </a>

        <p class="nav-section-title">Terrain</p>
        <a class="nav-link {{ request()->routeIs('missions.index') ? 'active' : '' }}"
           href="{{ route('missions.index') }}">
            <span class="ni" aria-hidden="true">◊</span>
            Services audités
        </a>
        <a class="nav-link {{ request()->routeIs('cartographie.*') ? 'active' : '' }}"
           href="{{ route('cartographie.select') }}">
            <span class="ni" aria-hidden="true">◐</span>
            Cartographie
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

        @if ($nav === 'inspection' && isset($sidebarDepartments) && $sidebarDepartments->isNotEmpty())
            <p class="nav-section-title">Filtrer par pôle</p>
            @foreach ($sidebarDepartments as $dept)
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
    @endif

    <p class="nav-section-title">Compte</p>
    <a class="nav-link {{ request()->routeIs('notifications.index') ? 'active' : '' }}"
       href="{{ route('notifications.index') }}">
        <span class="ni" aria-hidden="true">◉</span>
        Notifications
        <span class="nav-badge" data-notif-count
              style="{{ ($unreadNotificationsCount ?? 0) > 0 ? '' : 'display:none;' }}">{{ ($unreadNotificationsCount ?? 0) > 99 ? '99+' : ($unreadNotificationsCount ?? 0) }}</span>
    </a>
    <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
       href="{{ route('profile.edit') }}">
        <span class="ni" aria-hidden="true">●</span>
        Mon profil
    </a>
    <a class="nav-link {{ request()->routeIs('profile.security') ? 'active' : '' }}"
       href="{{ route('profile.security') }}">
        <span class="ni" aria-hidden="true">⌗</span>
        Sécurité du compte
    </a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-logout">Déconnexion</button>
    </form>
@endauth
