@props([
    'color' => 'blue',
    'size' => 'md',
    'variant' => 'solid',
    'pill' => false,
    'full' => false,
    'href' => null,
    'loading' => false,
])

<x-ui.button
    :color="$color"
    :size="$size"
    :variant="$variant"
    :pill="$pill"
    :full="$full"
    :href="$href"
    :loading="$loading"
    {{ $attributes }}
>
    {{ $slot }}
</x-ui.button>
