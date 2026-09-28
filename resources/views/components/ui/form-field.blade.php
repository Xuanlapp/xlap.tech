@props([
    'label',
    'for',
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->class(['space-y-2']) }}>
    <label for="{{ $for }}" class="block text-sm font-medium text-slate-800 dark:text-slate-200">
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
            <span class="sr-only">(bắt buộc)</span>
        @endif
    </label>

    {{ $slot }}

    @if ($error)
        <p class="text-sm text-red-600 dark:text-red-300" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
