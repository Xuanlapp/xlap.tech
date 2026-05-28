@props([
    'color' => 'slate',
])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'cyan' => 'bg-cyan-50 text-cyan-700',
        'green' => 'bg-green-50 text-green-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'red' => 'bg-red-50 text-red-700',
        'yellow' => 'bg-yellow-50 text-yellow-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'purple' => 'bg-purple-50 text-purple-700',
        'indigo' => 'bg-indigo-50 text-indigo-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg px-3 py-1 text-xs font-bold '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
