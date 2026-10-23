<div
    wire:poll.5s="pollRunningAssets"
    x-data="{
        activeTab: @js($status),
        setTab(tab) {
            if (this.activeTab === tab) { return; }
            this.activeTab = tab;
            localStorage.setItem('suncatcher-two.status-filter', tab);
            window.Livewire.dispatch('suncatcher-two-active-status-changed', { status: tab });
            window.dispatchEvent(new CustomEvent('suncatcher-two-tab-changed', { detail: { tab } }));
        },
    }"
>
    <div class="suncatcher-two-status-toolbar mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-wrap items-center gap-2" role="tablist" aria-label="Loc item theo trang thai">
            @foreach ([
                'all' => 'Tat ca',
                'unapproved' => 'Chua duyet',
                'approved' => 'Da duyet',
                'no_mockup' => 'No Mockup',
            ] as $tabStatus => $label)
                <button
                    type="button"
                    role="tab"
                    x-on:click="setTab('{{ $tabStatus }}')"
                    x-bind:aria-selected="activeTab === '{{ $tabStatus }}'"
                    x-bind:class="activeTab === '{{ $tabStatus }}'
                        ? 'border-cyan-300 bg-white text-cyan-700 shadow-sm dark:border-cyan-500/60 dark:bg-cyan-500/10 dark:text-cyan-300'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-white'"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border px-3 text-xs font-semibold transition"
                >
                    <span>{{ $label }}</span>
                    <span
                        x-bind:class="activeTab === '{{ $tabStatus }}' ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300' : 'bg-white text-slate-600 dark:bg-slate-700 dark:text-slate-200'"
                        class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px] font-bold"
                    >
                        {{ $statusCounts[$tabStatus] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>

        <button type="button" x-on:click="if (confirm('Xoa toan bo item chua duyet?')) $wire.clearAllUnapproved()" class="inline-flex h-9 items-center justify-center rounded-md border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-700 dark:border-rose-400/40 dark:bg-rose-500/10 dark:text-rose-300">Clear all</button>

        <x-offorest.pagination
            :paginator="$assets"
            :page-name="$pageName"
            class="border-t-0 p-0 sm:w-auto sm:min-w-[26rem]"
        />
    </div>

    <div class="space-y-5">
        @forelse ($assets as $asset)
            <div wire:key="suncatcher-two-{{ $status }}-asset-{{ $asset->id }}">
                <livewire:pages.suncatcher-two.product-design-card lazy
                    :asset-id="$asset->id"
                    :active-psd-template-name="$activePsdTemplateName"
                    :provider-key="$providerKey"
                    :image-model="$imageModel"
                    :text-model="$textModel"
                    :key="'suncatcher-two-'.$status.'-product-design-card-'.$asset->id.'-'.$providerKey.'-'.$imageModel.'-'.$textModel"
                />
            </div>
        @empty
            @if ($status !== 'unapproved')
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-100">Khong co item trong tab nay</p>
                </div>
            @endif
        @endforelse
    </div>

    <x-offorest.pagination :paginator="$assets" :page-name="$pageName" class="mt-6 rounded-lg border border-slate-200 shadow-sm" />
</div>


