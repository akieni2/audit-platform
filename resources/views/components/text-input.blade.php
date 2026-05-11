@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border border-white/10 bg-slate-950/60 text-slate-100 placeholder:text-slate-500 focus:border-dgcpt-cyan/50 focus:ring-dgcpt-cyan/40 shadow-inner']) }}>
