<div>
    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Loc ornament theo trang thai">
            @foreach ([
                'all' => 'Tat ca',
                'unapproved' => 'Chua duyet',
                'approved' => 'Da duyet',
            ] as $tabStatus => $label)
                <button
                    type="button"
                    role="tab"
                    x-on:click="setTab('{{ $tabStatus }}')"
                    x-bind:aria-selected="activeTab === '{{ $tabStatus }}'"
                    x-bind:class="activeTab === '{{ $tabStatus }}'
                        ? 'border-cyan-200 bg-white text-cyan-700 shadow-sm'
                        : 'border-transparent text-slate-500 hover:bg-white hover:text-slate-700'"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border px-3 text-xs font-semibold transition"
                >
                    <span>{{ $label }}</span>
                    <span
                        x-bind:class="activeTab === '{{ $tabStatus }}' ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-200 text-slate-600'"
                        class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px] font-bold"
                    >
                        {{ $statusCounts[$tabStatus] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>

        @if ($assets->hasPages())
            @php
                $firstPage = max(1, $assets->currentPage() - 1);
                $lastPage = min($assets->lastPage(), $assets->currentPage() + 1);
            @endphp

            <nav class="inline-flex self-end overflow-hidden rounded-md border border-slate-200 bg-slate-900 text-xs font-semibold text-slate-300 shadow-sm sm:self-auto" aria-label="Phan trang Ornament">
                <button
                    type="button"
                    wire:click="previousPage('{{ $pageName }}')"
                    @disabled($assets->onFirstPage())
                    class="inline-flex h-9 w-10 items-center justify-center border-r border-slate-700 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Trang truoc"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L9.06 10l3.71 3.71a.75.75 0 1 1-1.06 1.06l-4.24-4.24a.75.75 0 0 1 0-1.06l4.24-4.24a.75.75 0 0 1 1.08 0Z" clip-rule="evenodd" />
                    </svg>
                </button>

                @foreach (range($firstPage, $lastPage) as $page)
                    <button
                        type="button"
                        wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                        @disabled($page === $assets->currentPage())
                        class="inline-flex h-9 min-w-10 items-center justify-center border-r border-slate-700 px-3 transition hover:bg-slate-800 disabled:cursor-default disabled:bg-slate-800 disabled:text-white"
                        aria-label="Trang {{ $page }}"
                    >
                        {{ $page }}
                    </button>
                @endforeach

                <button
                    type="button"
                    wire:click="nextPage('{{ $pageName }}')"
                    @disabled(! $assets->hasMorePages())
                    class="inline-flex h-9 w-10 items-center justify-center transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Trang sau"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.24 4.24a.75.75 0 0 1 0 1.06l-4.24 4.24a.75.75 0 0 1-1.08 0Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </nav>
        @endif
    </div>

    <div class="space-y-5">
        @forelse ($assets as $asset)
            <livewire:pages.ornament.product-design-card
                :asset-id="$asset->id"
                :active-psd-template-name="$activePsdTemplateName"
                :key="'ornament-'.$status.'-product-design-card-'.$asset->id"
            />
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                <p class="text-base font-bold text-slate-800">Khong co item trong tab nay</p>
            </div>
        @endforelse
    </div>

    @if ($assets->hasPages())
        <div class="mt-6 flex justify-end">
            <nav class="inline-flex overflow-hidden rounded-md border border-slate-200 bg-slate-900 text-xs font-semibold text-slate-300 shadow-sm" aria-label="Phan trang Ornament duoi">
                <button
                    type="button"
                    wire:click="previousPage('{{ $pageName }}')"
                    @disabled($assets->onFirstPage())
                    class="inline-flex h-9 w-10 items-center justify-center border-r border-slate-700 transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Trang truoc"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L9.06 10l3.71 3.71a.75.75 0 1 1-1.06 1.06l-4.24-4.24a.75.75 0 0 1 0-1.06l4.24-4.24a.75.75 0 0 1 1.08 0Z" clip-rule="evenodd" />
                    </svg>
                </button>

                @foreach (range($firstPage, $lastPage) as $page)
                    <button
                        type="button"
                        wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                        @disabled($page === $assets->currentPage())
                        class="inline-flex h-9 min-w-10 items-center justify-center border-r border-slate-700 px-3 transition hover:bg-slate-800 disabled:cursor-default disabled:bg-slate-800 disabled:text-white"
                        aria-label="Trang {{ $page }}"
                    >
                        {{ $page }}
                    </button>
                @endforeach

                <button
                    type="button"
                    wire:click="nextPage('{{ $pageName }}')"
                    @disabled(! $assets->hasMorePages())
                    class="inline-flex h-9 w-10 items-center justify-center transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Trang sau"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.24 4.24a.75.75 0 0 1 0 1.06l-4.24 4.24a.75.75 0 0 1-1.08 0Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </nav>
        </div>
    @endif
</div>
