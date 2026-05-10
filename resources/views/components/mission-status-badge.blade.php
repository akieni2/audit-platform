@props(['status'])
@php
    $label = match ($status) {
        \App\Models\Mission::STATUS_BROUILLON => 'Brouillon',
        \App\Models\Mission::STATUS_EN_COURS => 'En cours',
        \App\Models\Mission::STATUS_CLOTUREE => 'Clôturée',
        \App\Models\Mission::STATUS_VALIDEE_IS => 'Validée IS',
        \App\Models\Mission::STATUS_VALIDEE_COPRI => 'Validée COPRI',
        default => (string) $status,
    };
    $tone = match ($status) {
        \App\Models\Mission::STATUS_BROUILLON => 'gray',
        \App\Models\Mission::STATUS_EN_COURS => 'blue',
        \App\Models\Mission::STATUS_CLOTUREE => 'amber',
        \App\Models\Mission::STATUS_VALIDEE_IS => 'indigo',
        \App\Models\Mission::STATUS_VALIDEE_COPRI => 'emerald',
        default => 'gray',
    };
    $classes = match ($tone) {
        'gray' => 'bg-slate-100 text-slate-800 ring-slate-200 dark:bg-slate-800/80 dark:text-slate-100 dark:ring-slate-600',
        'blue' => 'bg-sky-100 text-sky-900 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-100 dark:ring-sky-800',
        'amber' => 'bg-amber-100 text-amber-900 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800',
        'indigo' => 'bg-indigo-100 text-indigo-900 ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-100 dark:ring-indigo-800',
        'emerald' => 'bg-emerald-100 text-emerald-900 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-800',
        default => 'bg-slate-100 text-slate-800 ring-slate-200 dark:bg-slate-800/80 dark:text-slate-100 dark:ring-slate-600',
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.$classes]) }}>
    {{ $label }}
</span>
