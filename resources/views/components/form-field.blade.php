@props([
    'label' => null,
    'for' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if ($label)
        <x-label :for="$for" :required="$required">{{ $label }}</x-label>
    @endif

    {{ $slot }}

    @if ($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-sm text-gray-500">{{ $hint }}</p>
    @endif
</div>
