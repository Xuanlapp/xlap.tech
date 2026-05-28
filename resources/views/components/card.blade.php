@props([
    'padding' => 'md',
])

@php
    $paddingClass = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ][$padding] ?? 'p-6';
@endphp

<div {{ $attributes->merge(['class' => "rounded-2xl border border-slate-200 bg-white {$paddingClass} shadow-sm"]) }}>
    {{ $slot }}
</div>
