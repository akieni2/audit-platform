<nav x-data="{ open: false }" class="border-b border-gray-100 bg-white dark:border-[rgba(0,209,255,0.18)] dark:bg-[#050816]">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

<div class="flex justify-between h-16">

<div class="flex">

<!-- Logo -->
<div class="shrink-0 flex items-center">
<a href="{{ route('dashboard') }}">
<x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
</a>
</div>

<!-- Navigation Links -->
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

<!-- Dashboard -->
<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
Tableau de bord
</x-nav-link>

<!-- Missions -->
<x-nav-link :href="route('missions.index')" :active="request()->routeIs('missions.*')">
Missions
</x-nav-link>

<!-- Analyse -->
<x-nav-link :href="route('cartographie.select')" :active="request()->routeIs('cartographie.*')">
Cartographie
</x-nav-link>

<!-- Entretiens -->
<x-nav-link :href="route('module.entretiens')" :active="request()->routeIs('module.entretiens')">
Entretiens
</x-nav-link>

<!-- Processus -->
<x-nav-link :href="route('module.processus')" :active="request()->routeIs('module.processus')">
Processus
</x-nav-link>

<!-- Actifs -->
<x-nav-link :href="route('module.actifs')" :active="request()->routeIs('module.actifs')">
Actifs
</x-nav-link>

<!-- Risques -->
<x-nav-link :href="route('module.risques')" :active="request()->routeIs('module.risques')">
Risques
</x-nav-link>

<!-- Suivi -->
<x-nav-link :href="route('module.actions')" :active="request()->routeIs('module.actions')">
Actions correctives
</x-nav-link>

<!-- Rapports -->
<x-nav-link :href="route('module.rapports')" :active="request()->routeIs('module.rapports')">
Rapports
</x-nav-link>

</div>

</div>

<!-- User Menu -->
<div class="hidden sm:flex sm:items-center sm:ms-6">

<x-dropdown align="right" width="48">

<x-slot name="trigger">

<button class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition hover:text-gray-700 focus:outline-none dark:bg-[#10192B] dark:text-[#9FB3C8] dark:hover:bg-[#122038] dark:hover:text-[#E6EEF8]">

<div>{{ Auth::user()->name }}</div>

<div class="ms-1">
<svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
</svg>
</div>

</button>

</x-slot>

<x-slot name="content">

<x-dropdown-link :href="route('profile.edit')">
Mon profil
</x-dropdown-link>

<x-dropdown-link :href="route('profile.security')">
Sécurité du compte
</x-dropdown-link>

<x-dropdown-link :href="route('account.password')">
Changer le mot de passe
</x-dropdown-link>

<x-dropdown-link :href="route('profile.edit')">
Notifications
</x-dropdown-link>

<x-dropdown-link :href="route('profile.edit')">
Paramètres
</x-dropdown-link>

@can('manageUsers')
<x-dropdown-link :href="route('admin.users.index')">
Utilisateurs
</x-dropdown-link>
<x-dropdown-link :href="route('admin.users.create')">
Créer utilisateur
</x-dropdown-link>
<x-dropdown-link :href="route('admin.home')">
Tableau de bord admin
</x-dropdown-link>
<x-dropdown-link :href="route('admin.security.audit-logs')">
Journal sécurité
</x-dropdown-link>
@endcan

<form method="POST" action="{{ route('logout') }}">
@csrf

<x-dropdown-link :href="route('logout')"
onclick="event.preventDefault();
this.closest('form').submit();">

Déconnexion

</x-dropdown-link>

</form>

</x-slot>

</x-dropdown>

</div>

<!-- Mobile Menu -->
<div class="-me-2 flex items-center sm:hidden">

<button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:text-[#9FB3C8] dark:hover:bg-[#122038] dark:hover:text-[#E6EEF8]">

<svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">

<path :class="{'hidden': open, 'inline-flex': ! open }"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="2"
d="M4 6h16M4 12h16M4 18h16" />

<path :class="{'hidden': ! open, 'inline-flex': open }"
stroke-linecap="round"
stroke-linejoin="round"
stroke-width="2"
d="M6 18L18 6M6 6l12 12" />

</svg>

</button>

</div>

</div>

</div>

</nav>
