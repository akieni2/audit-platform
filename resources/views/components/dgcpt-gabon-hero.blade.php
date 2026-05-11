{{-- Stylised national map motif (decorative, not cartographic). --}}
<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center min-h-[280px] lg:min-h-[360px]']) }}>
    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-dgcpt-blue/40 via-dgcpt-ink to-black border border-dgcpt-cyan/20 shadow-[0_0_60px_rgba(0,209,255,0.12)]"></div>
    <div class="absolute inset-6 rounded-full border border-dgcpt-cyan/15 animate-pulse" style="animation-duration:4s;"></div>
    <div class="absolute inset-14 rounded-full border border-dgcpt-green/20"></div>
    <svg class="relative z-10 w-[72%] max-w-md opacity-95 drop-shadow-[0_0_18px_rgba(0,209,255,0.35)]" viewBox="0 0 320 400" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M155 32 L198 48 L220 95 L235 160 L228 240 L205 310 L165 355 L120 368 L78 340 L52 280 L48 200 L62 120 L95 58 Z"
              stroke="url(#dgcptMapGrad)" stroke-width="2.2" fill="rgba(10,42,102,0.35)"/>
        <circle cx="98" cy="120" r="4" fill="#00D1FF" class="animate-pulse"/>
        <circle cx="210" cy="180" r="3.5" fill="#F4D000"/>
        <circle cx="160" cy="260" r="3" fill="#00A86B"/>
        <circle cx="130" cy="200" r="2.5" fill="#00D1FF"/>
        <line x1="98" y1="120" x2="160" y2="200" stroke="rgba(0,209,255,0.35)" stroke-width="1"/>
        <line x1="210" y1="180" x2="160" y2="200" stroke="rgba(244,208,0,0.3)" stroke-width="1"/>
        <line x1="160" y1="200" x2="130" y2="200" stroke="rgba(0,168,107,0.35)" stroke-width="1"/>
        <defs>
            <linearGradient id="dgcptMapGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#00D1FF"/>
                <stop offset="50%" stop-color="#00A86B"/>
                <stop offset="100%" stop-color="#F4D000"/>
            </linearGradient>
        </defs>
    </svg>
    <p class="absolute bottom-6 left-0 right-0 text-center text-[0.65rem] font-bold uppercase tracking-[0.35em] text-dgcpt-cyan/80 z-10">Réseau national · supervision</p>
</div>
