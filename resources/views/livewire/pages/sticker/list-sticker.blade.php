<div class="min-h-[calc(100vh-4rem)] bg-[#f3f4f6] text-slate-950">
    <div class="mx-auto max-w-[1520px] px-4 py-5 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-cyan-600">Sticker workspace</p>
                <h1 class="text-2xl font-bold tracking-tight">List Sticker</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="$dispatch('openModal', { component: 'modals.prompt.detail-prompt', arguments: { productSlug: 'sticker' } })"
                    class="inline-flex h-10 items-center justify-center rounded-full border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50"
                >
                    Prompt
                </button>

                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-500 shadow-sm">
                    <span>{{ $assets->count() }} items</span>
                    <button
                        type="button"
                        wire:click="$dispatch('openModal', { component: 'modals.sticker.add-product-design' })"
                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-cyan-500 text-sm font-bold leading-none text-white hover:bg-cyan-600"
                        aria-label="Add sticker item"
                    >
                        +
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @forelse ($assets as $asset)
                <livewire:pages.sticker.product-design-card
                    :asset-id="$asset->id"
                    :key="'sticker-product-design-card-'.$asset->id"
                />
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                    <p class="text-base font-bold text-slate-800">Chua co du lieu Sticker</p>
                    <p class="mt-2 text-sm text-slate-500">Bam nut + canh so items de tao dong dau tien.</p>
                </div>
            @endforelse
        </div>
    </div>

    <livewire:modals.sticker.add-product-design />
    <livewire:modals.sticker.edit-product-detail />
    <livewire:modals.sticker.psd-mockup-template />
    <livewire:modals.prompt.detail-prompt />
</div>
