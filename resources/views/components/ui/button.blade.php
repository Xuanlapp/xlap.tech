@props([
    'color' => 'blue',
    'size' => 'md',
    'variant' => 'solid',
    'pill' => false,
    'full' => false,
    'href' => null,
    'loading' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 border font-medium text-center transition focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'xs' => 'px-3 py-2 text-xs',
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-5 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-base',
        'xl' => 'px-6 py-3.5 text-base',
    ];

    $solidColors = [
        'default' => 'border-blue-700 bg-blue-700 text-white hover:bg-blue-800 focus:ring-blue-300',
        'blue' => 'border-blue-700 bg-blue-700 text-white hover:bg-blue-800 focus:ring-blue-300',
        'dark' => 'border-gray-800 bg-gray-800 text-white hover:bg-gray-900 focus:ring-gray-300',
        'light' => 'border-gray-300 bg-white text-gray-900 hover:bg-gray-100 focus:ring-gray-200',
        'green' => 'border-green-700 bg-green-700 text-white hover:bg-green-800 focus:ring-green-300',
        'red' => 'border-red-700 bg-red-700 text-white hover:bg-red-800 focus:ring-red-300',
        'yellow' => 'border-yellow-400 bg-yellow-400 text-white hover:bg-yellow-500 focus:ring-yellow-300',
        'purple' => 'border-purple-700 bg-purple-700 text-white hover:bg-purple-800 focus:ring-purple-300',
        'cyan' => 'border-cyan-500 bg-cyan-500 text-white hover:bg-cyan-600 focus:ring-cyan-300',
        'emerald' => 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-300',
        'orange' => 'border-orange-500 bg-orange-500 text-white hover:bg-orange-600 focus:ring-orange-300',
        'slate' => 'border-slate-700 bg-slate-700 text-white hover:bg-slate-800 focus:ring-slate-300',
    ];

    $outlineColors = [
        'default' => 'border-blue-700 bg-transparent text-blue-700 hover:bg-blue-800 hover:text-white focus:ring-blue-300',
        'blue' => 'border-blue-700 bg-transparent text-blue-700 hover:bg-blue-800 hover:text-white focus:ring-blue-300',
        'dark' => 'border-gray-800 bg-transparent text-gray-800 hover:bg-gray-900 hover:text-white focus:ring-gray-300',
        'light' => 'border-gray-300 bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-200',
        'green' => 'border-green-700 bg-transparent text-green-700 hover:bg-green-800 hover:text-white focus:ring-green-300',
        'red' => 'border-red-700 bg-transparent text-red-700 hover:bg-red-800 hover:text-white focus:ring-red-300',
        'yellow' => 'border-yellow-400 bg-transparent text-yellow-500 hover:bg-yellow-500 hover:text-white focus:ring-yellow-300',
        'purple' => 'border-purple-700 bg-transparent text-purple-700 hover:bg-purple-800 hover:text-white focus:ring-purple-300',
        'cyan' => 'border-cyan-500 bg-transparent text-cyan-600 hover:bg-cyan-600 hover:text-white focus:ring-cyan-300',
        'emerald' => 'border-emerald-600 bg-transparent text-emerald-600 hover:bg-emerald-700 hover:text-white focus:ring-emerald-300',
        'orange' => 'border-orange-500 bg-transparent text-orange-500 hover:bg-orange-600 hover:text-white focus:ring-orange-300',
        'slate' => 'border-slate-700 bg-transparent text-slate-700 hover:bg-slate-800 hover:text-white focus:ring-slate-300',
    ];

    $softColors = [
        'default' => 'border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-200',
        'blue' => 'border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100 focus:ring-blue-200',
        'dark' => 'border-gray-200 bg-gray-100 text-gray-900 hover:bg-gray-200 focus:ring-gray-200',
        'light' => 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-200',
        'green' => 'border-green-100 bg-green-50 text-green-700 hover:bg-green-100 focus:ring-green-200',
        'red' => 'border-red-100 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-200',
        'yellow' => 'border-yellow-100 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 focus:ring-yellow-200',
        'purple' => 'border-purple-100 bg-purple-50 text-purple-700 hover:bg-purple-100 focus:ring-purple-200',
        'cyan' => 'border-cyan-100 bg-cyan-50 text-cyan-700 hover:bg-cyan-100 focus:ring-cyan-200',
        'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-200',
        'orange' => 'border-orange-100 bg-orange-50 text-orange-700 hover:bg-orange-100 focus:ring-orange-200',
        'slate' => 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-slate-200',
    ];

    $ghostColors = [
        'default' => 'border-transparent bg-transparent text-blue-700 hover:bg-blue-50 focus:ring-blue-200',
        'blue' => 'border-transparent bg-transparent text-blue-700 hover:bg-blue-50 focus:ring-blue-200',
        'dark' => 'border-transparent bg-transparent text-gray-800 hover:bg-gray-100 focus:ring-gray-200',
        'light' => 'border-transparent bg-transparent text-gray-600 hover:bg-gray-50 focus:ring-gray-200',
        'green' => 'border-transparent bg-transparent text-green-700 hover:bg-green-50 focus:ring-green-200',
        'red' => 'border-transparent bg-transparent text-red-700 hover:bg-red-50 focus:ring-red-200',
        'yellow' => 'border-transparent bg-transparent text-yellow-700 hover:bg-yellow-50 focus:ring-yellow-200',
        'purple' => 'border-transparent bg-transparent text-purple-700 hover:bg-purple-50 focus:ring-purple-200',
        'cyan' => 'border-transparent bg-transparent text-cyan-700 hover:bg-cyan-50 focus:ring-cyan-200',
        'emerald' => 'border-transparent bg-transparent text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-200',
        'orange' => 'border-transparent bg-transparent text-orange-600 hover:bg-orange-50 focus:ring-orange-200',
        'slate' => 'border-transparent bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-200',
    ];

    $variants = [
        'solid' => $solidColors,
        'outline' => $outlineColors,
        'soft' => $softColors,
        'ghost' => $ghostColors,
    ];

    $variantColors = $variants[$variant] ?? $solidColors;
    $shape = $pill ? 'rounded-full' : 'rounded-lg';
    $width = $full ? 'w-full' : '';
    $classes = collect([
        $base,
        $sizes[$size] ?? $sizes['md'],
        $variantColors[$color] ?? $variantColors['blue'],
        $shape,
        $width,
    ])->filter()->implode(' ');

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge([
        'type' => $href ? null : 'submit',
        'class' => $classes,
    ]) }}
>
    @if ($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
        </svg>
    @endif

    {{ $slot }}
</{{ $tag }}>
