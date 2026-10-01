<div>
    <div class="glass-status-toolbar mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <div class="flex min-w-0 gap-1 overflow-x-auto" role="tablist" aria-label="Loc glass theo trang thai">
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

            <div
                x-data="{
                    showMasterBounds: localStorage.getItem('glass-show-master-bounds') === '1',
                    masterBoundsOpacity: Number(localStorage.getItem('glass-master-bounds-opacity') || '0.72'),
                    init() {
                        window.addEventListener('glass-master-bounds-changed', (event) => {
                            this.showMasterBounds = Boolean(event.detail?.visible);
                            if (event.detail?.opacity !== undefined) this.masterBoundsOpacity = Number(event.detail.opacity);
                        });
                    },
                    toggleMasterBounds() {
                        this.showMasterBounds = ! this.showMasterBounds;
                        localStorage.setItem('glass-show-master-bounds', this.showMasterBounds ? '1' : '0');
                        window.dispatchEvent(new CustomEvent('glass-master-bounds-changed', { detail: { visible: this.showMasterBounds } }));
                    },
                }"
                class="shrink-0"
            >
                <button
                    type="button"
                    x-on:click="toggleMasterBounds()"
                    x-bind:aria-pressed="showMasterBounds.toString()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-md border px-2.5 text-xs font-semibold transition"
                    x-bind:class="showMasterBounds ? 'border-cyan-300 bg-cyan-50 text-cyan-700 dark:border-cyan-500/60 dark:bg-cyan-500/10 dark:text-cyan-300' : 'border-slate-200 bg-white text-slate-500 hover:border-cyan-200 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-cyan-500/50 dark:hover:text-cyan-300'"
                    title="Bật hoặc tắt khung bounds cho tất cả ảnh Create Master"
                >
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-cyan-500"></span>
                    <span x-text="showMasterBounds ? 'Ẩn bounds' : 'Hiện bounds'" ></span>
                </button>
                <label
                    x-show="showMasterBounds"
                    x-cloak
                    class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-200 bg-white px-2 dark:border-slate-700 dark:bg-slate-800"
                    title="Điều chỉnh độ đậm của khung bounds"
                >
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-300">Đậm</span>
                    <input
                        type="range"
                        min="0.1"
                        max="1"
                        step="0.05"
                        x-model.number="masterBoundsOpacity"
                        x-on:input="localStorage.setItem('glass-master-bounds-opacity', masterBoundsOpacity); window.dispatchEvent(new CustomEvent('glass-master-bounds-changed', { detail: { visible: showMasterBounds, opacity: masterBoundsOpacity } }))"
                        class="h-1.5 w-20 accent-cyan-500"
                        aria-label="Độ đậm bounds"
                    >
                </label>
            </div>
        </div>

        <x-offorest.pagination
            :paginator="$assets"
            :page-name="$pageName"
            class="border-t-0 p-0 sm:w-auto sm:min-w-[26rem]"
        />
    </div>

    <div class="space-y-5">
        @forelse ($assets as $asset)
            <livewire:pages.glass.product-design-card lazy
                :asset-id="$asset->id"
                :active-psd-template-name="$activePsdTemplateName"
                :provider-key="$providerKey"
                :image-model="$imageModel"
                :key="'glass-'.$status.'-product-design-card-'.$asset->id"
            />
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 dark:border-slate-700 dark:bg-slate-900 text-center shadow-sm">
                <p class="text-base font-bold text-slate-800">Khong co item trong tab nay</p>
            </div>
        @endforelse

    </div>

    <x-offorest.pagination :paginator="$assets" :page-name="$pageName" class="mt-6 rounded-lg border border-slate-200 shadow-sm" />
</div>
