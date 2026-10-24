@props([
    'paginator',
    'pageName' => null,
    'scrollTo' => false,
])

@php
    $pageName = $pageName ?: (method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page');
    $currentPage = $paginator->currentPage();
    $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null;
    $pages = [];

    if ($lastPage !== null) {
        $visiblePages = array_values(array_unique(array_filter([
            1,
            2,
            $currentPage - 1,
            $currentPage,
            $currentPage + 1,
            $lastPage - 1,
            $lastPage,
        ], fn (int $page): bool => $page >= 1 && $page <= $lastPage)));
        sort($visiblePages);

        foreach ($visiblePages as $index => $page) {
            if ($index > 0 && $page > $visiblePages[$index - 1] + 1) {
                $pages[] = '...';
            }
            $pages[] = $page;
        }
    }
@endphp

@if ($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'ml-auto flex w-fit max-w-full flex-col items-center gap-2']) }}>
        <nav
            aria-label="Phân trang"
            class="flex items-center justify-center gap-1.5"
            @if ($scrollTo !== false) data-scroll-target="{{ $scrollTo }}" x-on:click="document.querySelector($el.dataset.scrollTarget)?.scrollIntoView()" @endif
        >
            <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage()) aria-label="Trang trước" class="inline-flex size-8 items-center justify-center rounded-full border border-slate-300 text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none"><path d="m12 4-6 6 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>

            @foreach ($pages as $page)
                @if ($page === '...')
                    <span aria-hidden="true" class="px-1 text-sm text-slate-500">...</span>
                @elseif ($page === $currentPage)
                    <span aria-current="page" aria-label="Trang {{ $page }}, hiện tại" class="inline-flex size-8 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:loading.attr="disabled" aria-label="Đi tới trang {{ $page }}" class="inline-flex size-8 items-center justify-center rounded-full border border-slate-300 text-sm text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-wait disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ $page }}</button>
                @endif
            @endforeach

            <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages()) aria-label="Trang tiếp" class="inline-flex size-8 items-center justify-center rounded-full border border-slate-300 text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 20 20" fill="none"><path d="m8 4 6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
        </nav>

        @if ($lastPage !== null)
            <form x-data="{ page: '', lastPage: {{ $lastPage }}, pageName: @js($pageName) }" x-id="['goto-page']" x-on:submit.prevent="$wire.gotoPage(Math.min(Math.max(1, Number.parseInt(page, 10) || 1), lastPage), pageName); page = ''; @if ($scrollTo !== false) document.querySelector(@js($scrollTo))?.scrollIntoView() @endif" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <label :for="$id('goto-page')">Đi tới trang:</label>
                <input :id="$id('goto-page')" type="number" min="1" max="{{ $lastPage }}" x-model="page" inputmode="numeric" aria-label="Số trang cần tới" wire:loading.attr="disabled" wire:target="gotoPage" class="h-7 w-14 rounded-full border border-slate-300 bg-white px-2 text-center text-xs text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-wait disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <button type="submit" wire:loading.attr="disabled" wire:target="gotoPage" class="h-7 rounded-full bg-slate-200 px-3 font-medium text-slate-800 transition hover:bg-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-wait disabled:opacity-60 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">Đi</button>
            </form>
        @endif
    </div>
@endif
