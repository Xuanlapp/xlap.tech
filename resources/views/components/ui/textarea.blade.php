@props([
    'disabled' => false,
    'invalid' => false,
])

<textarea
    @disabled($disabled)
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class([
        'block min-h-28 w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:disabled:bg-slate-800',
        'border-red-400 focus:border-red-500 focus:ring-red-100 dark:border-red-500 dark:focus:ring-red-500/20' => $invalid,
        'border-slate-300 focus:border-blue-500 focus:ring-blue-100 dark:border-white/15 dark:focus:border-blue-400 dark:focus:ring-blue-400/20' => ! $invalid,
    ]) }}
>{{ $slot }}</textarea>
