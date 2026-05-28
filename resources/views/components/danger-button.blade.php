<x-ui.button color="red" size="sm" {{ $attributes->merge(['type' => 'submit', 'class' => 'uppercase tracking-widest']) }}>
    {{ $slot }}
</x-ui.button>
