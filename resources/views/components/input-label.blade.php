@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold uppercase tracking-wider text-slate-300']) }}>
    {{ $value ?? $slot }}
</label>
