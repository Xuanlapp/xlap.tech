<x-ui.button color="light" size="sm" {{ $attributes->merge(['type' => 'button', 'class' => 'uppercase tracking-widest shadow-sm']) }}>
    {{ $slot }}
</x-ui.button>
