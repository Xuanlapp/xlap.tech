<div>
    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Loc item theo trang thai">
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
                        ? 'border-cyan-200 bg-white text-cyan-700 shadow-sm dark:border-cyan-400/50 dark:bg-cyan-500/15 dark:text-cyan-300'
                        : 'border-transparent text-slate-500 hover:bg-white hover:text-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100'"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border px-3 text-xs font-semibold transition"
                >
                    <span>{{ $label }}</span>
                    <span
                        x-bind:class="activeTab === '{{ $tabStatus }}' ? 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200'"
                        class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px] font-bold"
                    >
                        {{ $statusCounts[$tabStatus] ?? 0 }}
                    </span>
                </button>
            @endforeach
        </div>

        <x-offorest.pagination
            :paginator="$assets"
            :page-name="$pageName"
            class="border-t-0 p-0 sm:w-auto sm:min-w-[26rem]"
        />
    </div>

    <div class="space-y-5">
        @forelse ($assets as $asset)
            <livewire:pages.decal.product-design-card lazy
                :asset-id="$asset->id"
                :active-psd-template-name="$activePsdTemplateName"
                :provider-key="$providerKey"
                :image-model="$imageModel"
                :key="'decal-'.$status.'-product-design-card-'.$asset->id"
            />
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 dark:border-slate-700 dark:bg-slate-900 text-center shadow-sm">
                <p class="text-base font-bold text-slate-800 dark:text-slate-100">Khong co item trong tab nay</p>
            </div>
        @endforelse

    </div>

    <x-offorest.pagination :paginator="$assets" :page-name="$pageName" class="mt-6 rounded-lg border border-slate-200 shadow-sm" />
</div>
