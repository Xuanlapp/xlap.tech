@props([
    'value' => null,
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'mb-2 block text-sm font-medium text-gray-900']) }}>
    {{ $value ?? $slot }}

    @if ($required)
        <span class="text-red-600">*</span>
    @endif
</label>
