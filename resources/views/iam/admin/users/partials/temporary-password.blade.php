@if (session('temporary_password'))
    @php($temporaryAccess = session('temporary_password'))
    <div class="rounded-xl border border-[rgba(244,208,0,0.5)] bg-[#10192B] p-5 text-[#E6EEF8] shadow-sm" x-data="{ copied: false }">
        <p class="text-sm font-bold uppercase tracking-wider text-[#F4D000]">Mot de passe temporaire — affichage unique</p>
        <p class="mt-2 text-sm text-[#9FB3C8]">{{ $temporaryAccess['display_name'] }} · {{ $temporaryAccess['email'] }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <code class="rounded-lg border border-[rgba(0,209,255,0.25)] bg-[#050816] px-4 py-2 font-mono text-base text-[#E6EEF8]">{{ $temporaryAccess['password'] }}</code>
            <button type="button" class="dgcpt-btn-outline" @click="navigator.clipboard.writeText(@js($temporaryAccess['password'])); copied = true">
                <span x-show="!copied">Copier</span>
                <span x-show="copied" x-cloak>Copié</span>
            </button>
        </div>
        <p class="mt-3 text-xs text-[#FFB020]">Remettez-le par un canal sécurisé. Il ne sera plus affiché après avoir quitté ou actualisé cette page.</p>
    </div>
@endif
