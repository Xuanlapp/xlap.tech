<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-black/[0.02]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <span class="inline-flex h-8 shrink-0 items-center rounded-lg bg-indigo-50 px-3 text-xs font-bold text-indigo-600">
                STT: {{ $asset->item_number }}
            </span>

            <h2 class="min-w-0 truncate text-lg font-bold text-slate-950">
                {{ $asset->keyword ?: 'Sticker item' }}
            </h2>

            <x-button
                color="slate"
                variant="ghost"
                size="xs"
                type="button"
                wire:click="$dispatch('openModal', { component: 'modals.sticker.edit-product-detail', arguments: { assetId: {{ $asset->id }} } })"
            >
                Edit item
            </x-button>
        </div>

        <label class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600">
            <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Create
        </label>
    </div>

    @if ($statusMessage)
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-4">
        <div class="min-w-0">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-slate-600">1. Source Image</x-label>
            </div>

            <x-image-preview reviewable class="aspect-[4/4.45] rounded-xl border border-slate-200 bg-slate-50" :src="$asset->image_preview_url" :original="$asset->image_link" alt="Source image">
                <span class="px-4 text-center text-sm font-medium text-slate-400">Dan link anh nguon vao day</span>
            </x-image-preview>

            <div class="mt-2 flex min-h-10 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                @if ($asset->image_link)
                    <button
                        type="button"
                        wire:click="$dispatch('review-image', { src: @js($asset->image_preview_url), original: @js($asset->image_link), title: 'Source image' })"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-700"
                    >
                        Xem anh nguon
                    </button>
                @else
                    <span class="text-xs font-medium text-slate-400">Chua co anh nguon</span>
                @endif
            </div>
        </div>

        <div class="min-w-0 {{ $asset->image_link ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-blue-600">2. Create Master</x-label>
                @if ($asset->image_link)
                    <x-ui.button color="blue" variant="ghost" size="xs" type="button" wire:click="generateRedesign" wire:loading.attr="disabled" class="shrink-0">
                        Create Master
                    </x-ui.button>
                @endif
            </div>

            <x-image-preview reviewable class="aspect-[4/4.45] rounded-xl border border-slate-200 bg-slate-50" :src="$asset->redesign_preview_url" :original="$asset->redesign" alt="Redesign image">
                <span class="px-4 text-center text-sm font-medium text-slate-400">
                    {{ $asset->image_link ? 'Waiting for creation...' : 'Cho anh nguon' }}
                </span>
            </x-image-preview>
        </div>

        <div class="min-w-0 {{ $asset->redesign ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-emerald-700">3. Lifestyle Image</x-label>
                @if ($asset->redesign)
                    <x-ui.button color="emerald" variant="ghost" size="xs" type="button" wire:click="generateFinalImages" wire:loading.attr="disabled" class="shrink-0">
                        Generate Lifestyle
                    </x-ui.button>
                @endif
            </div>

            <x-image-preview reviewable class="aspect-[4/4.45] rounded-xl border border-slate-200 bg-slate-50" :src="$asset->mockup1_preview_url" :original="$asset->mockup1" alt="Lifestyle image">
                <span class="px-4 text-center text-sm font-medium text-slate-400">
                    {{ $asset->redesign ? 'Bam Generate de tao lifestyle' : 'Cho anh master' }}
                </span>
            </x-image-preview>

            <p class="mt-2 text-xs italic text-slate-500">Lifestyle co the upload, khong co thi bo qua.</p>
        </div>

        <div class="min-w-0 {{ $asset->redesign ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-orange-600">4. Mockup Tu Chon</x-label>
                @if ($asset->redesign)
                    <x-ui.button color="orange" variant="ghost" size="xs" type="button" wire:click="generateFinalImages" wire:loading.attr="disabled" class="shrink-0">
                        Generate PSD
                    </x-ui.button>
                @endif
            </div>

            <x-image-preview reviewable class="aspect-[4/4.45] rounded-xl border border-slate-200 bg-slate-50" :src="$asset->mockup2_preview_url" :original="$asset->mockup2" alt="Mockup image">
                <span class="px-4 text-center text-sm font-medium text-slate-400">
                    {{ $asset->redesign ? 'Chon anh mockup cua ban' : 'Cho anh master' }}
                </span>
            </x-image-preview>

            <p class="mt-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs text-slate-500">Template rieng: Dang dung template mac dinh.</p>
        </div>
    </div>
</article>
