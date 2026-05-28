@props([
    'type' => 'info',
])

@php
    $types = [
        'info' => 'border-blue-200 bg-blue-50 text-blue-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'neutral' => 'border-slate-200 bg-slate-50 text-slate-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 text-sm font-medium '.($types[$type] ?? $types['info'])]) }}>
    {{ $slot }}
</div>
