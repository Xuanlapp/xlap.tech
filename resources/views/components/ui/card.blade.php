@props([
    'title' => null,
    'description' => null,
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

<section {{ $attributes->class(['rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900', $paddingClass]) }}>
    @if ($title || $description)
        <header class="mb-5 space-y-1.5">
            @if ($title)
                <h2 class="text-lg font-semibold tracking-tight text-slate-950 dark:text-white">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
