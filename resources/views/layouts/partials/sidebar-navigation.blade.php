@php
    $nav = $institutionalNavMode ?? 'department';
@endphp

@auth
    @if (($canAccessCopriNav ?? false) && $nav !== 'copri')
        <p class="nav-section-title">Pilotage COPRI</p>
        <a class="nav-link {{ request()->routeIs('dashboard.executive') ? 'active' : '' }}"
           href="{{ route('dashboard.executive') }}">
            <span class="ni" aria-hidden="true">◇</span>
            Espace COPRI
        </a>
    @endif
    @if ($nav === 'technical_admin')
        @if (($canManageUsers ?? false) || ($canManageDepartmentsNav ?? false) || ($canViewOrganizationChartNav ?? false))
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
                    @if ($canAccessAdminConsoleNav ?? false)
                        <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}"
                           href="{{ route('admin.home') }}">
                            <span class="ni" aria-hidden="true">◎</span>
                            Console administration
                        </a>
                    @endif
                    @if ($canAccessSecurityLogsNav ?? false)
                        <a class="nav-link {{ request()->routeIs('admin.security.audit-logs') ? 'active' : '' }}"
                           href="{{ route('admin.security.audit-logs') }}">
                            <span class="ni" aria-hidden="true">▤</span>
                            Journal sécurité
                        </a>
                    @endif
                @endif
                @can('manageEnrollmentRequests')
                    <a class="nav-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}"
                       href="{{ route('admin.enrollments.index') }}">
                        <span class="ni" aria-hidden="true">✉</span>
                        Demandes d'enrôlement
                        @if (($pendingEnrollmentsCount ?? 0) > 0)
                            <span class="nav-badge">{{ $pendingEnrollmentsCount > 99 ? '99+' : $pendingEnrollmentsCount }}</span>
                        @endif
                    </a>
                @endcan
                @if ($canManageDepartmentsNav ?? false)
                    <a class="nav-link {{ request()->routeIs(['admin.departments.index', 'admin.departments.create', 'admin.departments.edit']) ? 'active' : '' }}"
                       href="{{ route('admin.departments.index') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        Structures et directions
                    </a>
                    <a class="nav-link {{ request()->routeIs('enterprise.methodologies') ? 'active' : '' }}"
                       href="{{ route('enterprise.methodologies') }}">
                        <span class="ni" aria-hidden="true">⌘</span>
                        Gestion des référentiels
                    </a>
                @endif
                @if ($canViewOrganizationChartNav ?? false)
                    <a class="nav-link {{ request()->routeIs('admin.departments.organigramme') ? 'active' : '' }}"
                       href="{{ route('admin.departments.organigramme') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        {{ auth()->user()?->canViewGlobalOrganization() ? 'Organigramme global' : 'Organigramme du département' }}
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

        @if (($canManageUsers ?? false) || ($canManageDepartmentsNav ?? false) || ($canViewOrganizationChartNav ?? false))
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
                    @if ($canAccessAdminConsoleNav ?? false)
                        <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}"
                           href="{{ route('admin.home') }}">
                            <span class="ni" aria-hidden="true">◎</span>
                            Tableau de bord admin
                        </a>
                    @endif
                    @if ($canAccessSecurityLogsNav ?? false)
                        <a class="nav-link {{ request()->routeIs('admin.security.audit-logs') ? 'active' : '' }}"
                           href="{{ route('admin.security.audit-logs') }}">
                            <span class="ni" aria-hidden="true">▤</span>
                            Journal sécurité
                        </a>
                    @endif
                @endif
                @can('manageEnrollmentRequests')
                    <a class="nav-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}"
                       href="{{ route('admin.enrollments.index') }}">
                        <span class="ni" aria-hidden="true">✉</span>
                        Demandes d'enrôlement
                        @if (($pendingEnrollmentsCount ?? 0) > 0)
                            <span class="nav-badge">{{ $pendingEnrollmentsCount > 99 ? '99+' : $pendingEnrollmentsCount }}</span>
                        @endif
                    </a>
                @endcan
                @if ($canManageDepartmentsNav ?? false)
                    <a class="nav-link {{ request()->routeIs(['admin.departments.index', 'admin.departments.create', 'admin.departments.edit']) ? 'active' : '' }}"
                       href="{{ route('admin.departments.index') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        Structures et directions
                    </a>
                    <a class="nav-link {{ request()->routeIs('enterprise.methodologies') ? 'active' : '' }}"
                       href="{{ route('enterprise.methodologies') }}">
                        <span class="ni" aria-hidden="true">⌘</span>
                        Gestion des référentiels
                    </a>
                @endif
                @if ($canViewOrganizationChartNav ?? false)
                    <a class="nav-link {{ request()->routeIs('admin.departments.organigramme') ? 'active' : '' }}"
                       href="{{ route('admin.departments.organigramme') }}">
                        <span class="ni" aria-hidden="true">◫</span>
                        {{ auth()->user()?->canViewGlobalOrganization() ? 'Organigramme global' : 'Organigramme du département' }}
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
        @can('create', \App\Models\Mission::class)
            <a class="nav-link {{ request()->routeIs('missions.create') ? 'active' : '' }}"
               href="{{ route('missions.create') }}">
                <span class="ni" aria-hidden="true">＋</span>
                Nouvelle mission
            </a>
        @endcan

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
        <a class="nav-link {{ request()->routeIs('dgcpt.*') ? 'active' : '' }}"
           href="{{ route('dgcpt.hierarchy.index') }}">
            <span class="ni" aria-hidden="true">▣</span>
            Hiérarchie DGCPT
        </a>

        <p class="nav-section-title">Analyse</p>
        <a class="nav-link {{ request()->routeIs('questionnaire-builder.*') || request()->routeIs('questionnaire-templates.*') ? 'active' : '' }}"
           href="{{ route('questionnaire-builder.index') }}">
            <span class="ni" aria-hidden="true">≋</span>
            Questionnaires
        </a>
        <a class="nav-link {{ request()->routeIs('workflow-builder.*') ? 'active' : '' }}"
           href="{{ route('workflow-builder.index') }}">
            <span class="ni" aria-hidden="true">⇄</span>
            Workflows
        </a>
        <a class="nav-link {{ request()->routeIs('workflow-runtime.*') ? 'active' : '' }}"
           href="{{ route('workflow-runtime.dashboard') }}">
            <span class="ni" aria-hidden="true">▤</span>
            Exécution des workflows
        </a>
        @unless ($canManageDepartmentsNav ?? false)
            <a class="nav-link {{ request()->routeIs('enterprise.methodologies') ? 'active' : '' }}"
               href="{{ route('enterprise.methodologies') }}">
                <span class="ni" aria-hidden="true">⌘</span>
                Référentiel de l’unité
            </a>
        @endunless
        <a class="nav-link {{ request()->routeIs('enterprise.taxonomies') ? 'active' : '' }}"
           href="{{ route('enterprise.taxonomies') }}">
            <span class="ni" aria-hidden="true">⋔</span>
            Taxonomies
        </a>
        <a class="nav-link {{ request()->routeIs('enterprise.controls') ? 'active' : '' }}"
           href="{{ route('enterprise.controls') }}">
            <span class="ni" aria-hidden="true">☑</span>
            Contrôles
        </a>
        <a class="nav-link {{ request()->routeIs('swot-builder.*') || request()->routeIs('swot.*') ? 'active' : '' }}"
           href="{{ route('swot-builder.index') }}">
            <span class="ni" aria-hidden="true">⬒</span>
            SWOT
        </a>
        <a class="nav-link {{ request()->routeIs('raci-builder.*') || request()->routeIs('raci.*') ? 'active' : '' }}"
           href="{{ route('raci-builder.index') }}">
            <span class="ni" aria-hidden="true">⌗</span>
            RACI
        </a>
        <a class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}"
           href="{{ route('ai.index') }}">
            <span class="ni" aria-hidden="true">✦</span>
            Copilote IA
        </a>
        <a class="nav-link {{ request()->routeIs('form-builder.*') ? 'active' : '' }}"
           href="{{ route('form-builder.index') }}">
            <span class="ni" aria-hidden="true">▣</span>
            Formulaires
        </a>
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
        <a class="nav-link {{ request()->routeIs('risks.review-board') ? 'active' : '' }}"
           href="{{ route('risks.review-board') }}">
            <span class="ni" aria-hidden="true">◌</span>
            Comité de revue
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
        <a class="nav-link {{ request()->routeIs('enterprise.consolidation') ? 'active' : '' }}"
           href="{{ route('enterprise.consolidation') }}">
            <span class="ni" aria-hidden="true">⇱</span>
            Consolidation
        </a>

        @can('viewExecutiveDashboard')
            <p class="nav-section-title">Executive</p>
            <a class="nav-link {{ request()->routeIs('executive.national-dashboard') ? 'active' : '' }}"
               href="{{ route('executive.national-dashboard') }}">
                <span class="ni" aria-hidden="true">◈</span>
                Executive
            </a>
            <a class="nav-link {{ request()->routeIs('executive.risk-intelligence') ? 'active' : '' }}"
               href="{{ route('executive.risk-intelligence') }}">
                <span class="ni" aria-hidden="true">✦</span>
                Intelligence
            </a>
            <a class="nav-link {{ request()->routeIs('executive.governance-overview') ? 'active' : '' }}"
               href="{{ route('executive.governance-overview') }}">
                <span class="ni" aria-hidden="true">◫</span>
                Analytics
            </a>
            <a class="nav-link {{ request()->routeIs('executive.swot-dashboard') ? 'active' : '' }}"
               href="{{ route('executive.swot-dashboard') }}">
                <span class="ni" aria-hidden="true">⬒</span>
                Tableau de bord SWOT
            </a>
            <a class="nav-link {{ request()->routeIs('executive.raci-dashboard') ? 'active' : '' }}"
               href="{{ route('executive.raci-dashboard') }}">
                <span class="ni" aria-hidden="true">⌗</span>
                Tableau de bord RACI
            </a>
        @endcan

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
