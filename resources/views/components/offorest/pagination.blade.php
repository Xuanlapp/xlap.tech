@props([
    'paginator',
    'pageName' => null,
])

@php
    $pageName = $pageName ?: (method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page');
    $firstItem = $paginator->firstItem() ?: 0;
    $lastItem = $paginator->lastItem() ?: 0;
    $total = method_exists($paginator, 'total') ? $paginator->total() : $paginator->count();
@endphp

@if ($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between']) }}>
        <p class="text-sm text-slate-500">
            Dang hien <span>{{ $firstItem }}</span>-<span>{{ $lastItem }}</span>
            tren <span>{{ $total }}</span> ket qua
        </p>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="previousPage('{{ $pageName }}')"
                @disabled($paginator->onFirstPage())
                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Previous
            </button>
            <span class="rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}
            </span>
            <button
                type="button"
                wire:click="nextPage('{{ $pageName }}')"
                @disabled(! $paginator->hasMorePages())
                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Next
            </button>
        </div>
    </div>
@endif
