<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-dgcpt-cyan/35 bg-gradient-to-r from-dgcpt-blue to-blue-950 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg shadow-cyan-500/15 transition hover:shadow-cyan-500/25 focus:outline-none focus:ring-2 focus:ring-dgcpt-cyan focus:ring-offset-2 focus:ring-offset-slate-950']) }}>
    {{ $slot }}
</button>
