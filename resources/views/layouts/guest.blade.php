<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=DM+Sans:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen antialiased text-slate-100">
<div class="fixed inset-0 bg-dgcpt-ink"></div>
<div class="fixed inset-0 opacity-[0.35] bg-[radial-gradient(ellipse_at_top,_rgba(0,209,255,0.14),transparent_55%),radial-gradient(ellipse_at_bottom,_rgba(10,42,102,0.5),transparent_50%)]"></div>
<div class="fixed inset-0 opacity-30 bg-[linear-gradient(rgba(0,209,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(0,209,255,0.04)_1px,transparent_1px)] bg-[length:40px_40px]"></div>

<div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-5 sm:px-6 sm:py-10">
    <a href="{{ url('/') }}" class="mb-5 flex flex-col items-center gap-2 text-center transition hover:opacity-95 sm:mb-8 sm:gap-3">
        <img src="{{ asset('assets/branding/dgcpt-logo.png') }}" alt="DGCPT" class="h-20 w-20 rounded-full object-contain shadow-[0_0_40px_rgba(0,209,255,0.25)] ring-2 ring-dgcpt-cyan/30 sm:h-24 sm:w-24">
        <span class="max-w-[20rem] text-[0.62rem] font-bold uppercase leading-relaxed tracking-[0.08em] text-dgcpt-cyan/90 sm:max-w-md sm:text-[0.65rem] sm:tracking-[0.16em]">DGCPT — Direction Générale de la Comptabilité Publique et du Trésor</span>
    </a>

    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-slate-950/60 p-4 shadow-2xl shadow-black/50 backdrop-blur-xl sm:p-8">
        {{ $slot }}
    </div>

    <p class="mt-5 max-w-md px-3 text-center text-[0.7rem] leading-relaxed text-slate-500 sm:mt-8 sm:text-xs">
        Accès réservé aux agents habilités. Toute connexion est journalisée.
    </p>
</div>
</body>
</html>
