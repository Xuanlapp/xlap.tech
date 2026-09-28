@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-200',
        'info' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
        'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-200',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $variants[$variant] ?? $variants['neutral']]) }}>
    {{ $slot }}
</span>
