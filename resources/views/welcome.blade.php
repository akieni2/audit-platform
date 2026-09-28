<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('dgcpt.institution_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/dgcpt-logo.png') }}">
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme') === 'light') document.documentElement.classList.remove('dark');
                else document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dgcpt-dashboard.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&family=DM+Sans:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen antialiased bg-dgcpt-ink text-slate-100">
<div class="fixed inset-0 pointer-events-none opacity-40 bg-[linear-gradient(rgba(0,209,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(0,209,255,0.05)_1px,transparent_1px)] bg-[length:56px_56px]"></div>

<header class="relative z-20 border-b border-white/5 bg-dgcpt-ink/80 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('assets/branding/dgcpt-logo.png') }}" alt="" class="h-12 w-12 shrink-0 rounded-full object-contain shadow-[0_0_20px_rgba(0,209,255,0.25)]" width="48" height="48">
            <div class="min-w-0">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-dgcpt-cyan/90">DGCPT</p>
                <p class="max-w-sm text-[0.7rem] font-semibold leading-snug text-slate-300 sm:text-xs">Direction Générale de la Comptabilité Publique et du Trésor</p>
            </div>
        </div>
        <nav class="grid w-full grid-cols-2 gap-2 text-sm font-semibold sm:flex sm:w-auto sm:items-center">
            @auth
                <x-ui.glow-button href="{{ url('/dashboard') }}" variant="primary" class="col-span-2 w-full px-3 text-[0.7rem] sm:w-auto sm:px-5 sm:text-sm">Tableau de bord</x-ui.glow-button>
            @else
                <x-ui.glow-button href="{{ route('login') }}" variant="outline" class="w-full px-3 text-[0.68rem] tracking-wider sm:w-auto sm:px-5 sm:text-sm sm:tracking-widest">Connexion</x-ui.glow-button>
                @if (Route::has('register'))
                    <x-ui.glow-button href="{{ route('register') }}" variant="primary" class="w-full px-3 text-[0.68rem] tracking-wider sm:w-auto sm:px-5 sm:text-sm sm:tracking-widest">Demande d’accès</x-ui.glow-button>
                @endif
            @endauth
        </nav>
    </div>
</header>

<main class="relative z-10 mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:py-12 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-20">
    <div class="min-w-0 space-y-6 sm:space-y-8">
        <div class="inline-flex max-w-full items-center gap-2 rounded-2xl border border-dgcpt-cyan/25 bg-dgcpt-blue/30 px-3 py-2 text-[0.6rem] font-bold uppercase leading-relaxed tracking-[0.14em] text-dgcpt-cyan sm:rounded-full sm:py-1 sm:text-[0.65rem] sm:tracking-[0.2em]">
            Centre de contrôle institutionnel
        </div>
        <h1 class="break-words text-[1.8rem] font-extrabold uppercase leading-[1.12] tracking-[-0.025em] text-[#0B1220] min-[380px]:text-[2rem] sm:text-4xl lg:text-[2.35rem] lg:leading-[1.12]">
            Plateforme d’audit,<br class="hidden sm:block">
            de gouvernance<br class="hidden sm:block">
            et de suivi de performance<br class="hidden sm:block">
            <span class="text-[#00D1FF]">de la Direction Générale de la Comptabilité Publique et du Trésor</span>
        </h1>
        <p class="max-w-xl text-base leading-relaxed text-slate-400 sm:text-lg">
            Centre intelligent de contrôle, d’audit, de gouvernance et de pilotage de la performance institutionnelle.
        </p>
        <div class="grid gap-3 sm:flex sm:flex-wrap sm:gap-4">
            @auth
                <x-ui.glow-button href="{{ route('dashboard') }}" class="w-full sm:w-auto">Accéder au tableau de bord</x-ui.glow-button>
                <x-ui.glow-button href="{{ route('module.rapports') }}" variant="outline" class="w-full sm:w-auto">Voir les rapports</x-ui.glow-button>
            @else
                <x-ui.glow-button href="{{ route('login') }}" class="w-full sm:w-auto">Accéder au tableau de bord</x-ui.glow-button>
                <x-ui.glow-button href="{{ route('login') }}" variant="outline" class="w-full sm:w-auto">Voir les rapports</x-ui.glow-button>
            @endauth
        </div>
        <div class="flex flex-wrap gap-6 border-t border-white/10 pt-8 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-500">
            <span>Transparence</span>
            <span class="text-dgcpt-cyan/80">Gouvernance</span>
            <span>Performance</span>
            <span>Redevabilité</span>
        </div>
    </div>
    <x-dgcpt-gabon-hero />
</main>

<footer class="relative z-10 border-t border-white/5 bg-black/40 py-6 text-center text-xs text-slate-500">
    © {{ date('Y') }} DGCPT — Direction Générale de la Comptabilité Publique et du Trésor. Tous droits réservés.
    <span class="mx-2 text-slate-600">·</span>
    Plateforme sécurisée — données institutionnelles protégées.
</footer>
</body>
</html>
