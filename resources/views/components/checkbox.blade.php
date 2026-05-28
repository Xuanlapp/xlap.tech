@props([
    'disabled' => false,
    'label' => null,
])

<label class="inline-flex items-center gap-2">
    <input
        type="checkbox"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'rounded border-gray-300 text-cyan-600 shadow-sm focus:ring-cyan-500 disabled:opacity-50']) !!}
    >

    @if ($label)
        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
    @elseif ($slot->isNotEmpty())
        <span class="text-sm font-medium text-gray-700">{{ $slot }}</span>
    @endif
</label>
