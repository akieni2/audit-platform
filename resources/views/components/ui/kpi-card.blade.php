@props([
    'label',
    'value',
    'accent' => 'default',
])

@php
    $accent = in_array($accent, ['default', 'green', 'yellow', 'cyan', 'danger', 'violet'], true) ? $accent : 'default';
@endphp

<div {{ $attributes->merge(['class' => 'dgcpt-kpi-card dgcpt-kpi-card--'.$accent]) }}>
    <div class="dgcpt-kpi-label">{{ $label }}</div>
    <div class="dgcpt-kpi-value">{{ $value }}</div>
    {{ $slot }}
</div>
