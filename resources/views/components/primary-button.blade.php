<x-ui.button color="dark" size="sm" {{ $attributes->merge(['type' => 'submit', 'class' => 'uppercase tracking-widest']) }}>
    {{ $slot }}
</x-ui.button>
