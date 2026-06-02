<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-black/[0.02]">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <span class="inline-flex h-8 shrink-0 items-center rounded-lg bg-indigo-50 px-3 text-xs font-bold text-indigo-600">
                STT: {{ $asset->item_number }}
            </span>

            <h2 class="min-w-0 truncate text-lg font-bold text-slate-950">
                {{ $asset->keyword ?: 'Ornament item' }}
            </h2>

            @if (! $asset->is_approved && ! $asset->redesign)
                <x-button
                    color="slate"
                    variant="ghost"
                    size="xs"
                    type="button"
                    wire:click="$dispatch('openModal', { component: 'modals.ornament.edit-product-detail', arguments: { assetId: {{ $asset->id }} } })"
                >
                    Edit item
                </x-button>
            @endif

            @if ($asset->is_approved)
                <x-badge color="green">
                    Da duyet
                </x-badge>
            @elseif ($asset->hasApprovableOutput())
                <x-button
                    color="cyan"
                    variant="solid"
                    size="xs"
                    type="button"
                    wire:click="toggleApproval"
                    wire:loading.attr="disabled"
                    wire:target="toggleApproval"
                >
                    <span wire:loading.remove wire:target="toggleApproval">
                        Duyet
                    </span>
                    <span wire:loading wire:target="toggleApproval">Saving...</span>
                </x-button>
            @endif
        </div>

        <x-label class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600">
            <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Create
        </x-label>
    </div>

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
                @if ($asset->image_link && ! $asset->is_approved)
                    <x-ui.button color="blue" variant="ghost" size="xs" type="button" x-on:click="window.dispatchEvent(new CustomEvent('ornament-generation-started'))" wire:click="generateRedesign" wire:loading.attr="disabled" wire:target="generateRedesign" class="shrink-0">
                        <span wire:loading.remove wire:target="generateRedesign">Create Master</span>
                        <span wire:loading wire:target="generateRedesign">Creating...</span>
                    </x-ui.button>
                @endif
            </div>

            <div class="relative aspect-[4/4.45] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <div wire:loading.flex wire:target="generateRedesign" class="absolute inset-0 z-10 bg-slate-50">
                    <x-spinner />
                </div>

                <div wire:loading.class="invisible" wire:target="generateRedesign" class="h-full w-full">
                    <x-image-preview reviewable class="h-full w-full" :src="$asset->redesign_preview_url" :original="$asset->redesign" alt="Redesign image">
                        <span class="px-4 text-center text-sm font-medium text-slate-400">
                            {{ $asset->image_link ? 'Waiting for creation...' : 'Cho anh nguon' }}
                        </span>
                    </x-image-preview>
                </div>
            </div>
        </div>

        <div class="min-w-0 {{ $asset->redesign ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-emerald-700">3. Lifestyle Image</x-label>
                @if ($asset->redesign && ! $asset->is_approved)
                    <x-ui.button color="emerald" variant="ghost" size="xs" type="button" x-on:click="window.dispatchEvent(new CustomEvent('ornament-generation-started'))" wire:click="generateFinalImages" wire:loading.attr="disabled" wire:target="generateFinalImages" class="shrink-0">
                        <span wire:loading.remove wire:target="generateFinalImages">Generate Lifestyle</span>
                        <span wire:loading wire:target="generateFinalImages">Generating...</span>
                    </x-ui.button>
                @endif
            </div>

            <div class="relative aspect-[4/4.45] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <div wire:loading.flex wire:target="generateFinalImages" class="absolute inset-0 z-10 bg-slate-50">
                    <x-spinner />
                </div>

                <div wire:loading.class="invisible" wire:target="generateFinalImages" class="h-full w-full p-2">
                    @php
                        $lifestyleImages = collect([
                            ['src' => $asset->lifestyle1_preview_url, 'original' => $asset->lifestyle1, 'label' => 'Lifestyle 1'],
                            ['src' => $asset->lifestyle2_preview_url, 'original' => $asset->lifestyle2, 'label' => 'Lifestyle 2'],
                            ['src' => $asset->lifestyle3_preview_url, 'original' => $asset->lifestyle3, 'label' => 'Lifestyle 3'],
                        ])->filter(fn ($image) => filled($image['original']));
                    @endphp

                    @if ($lifestyleImages->isNotEmpty())
                        <div class="grid h-full grid-cols-3 gap-2">
                            @foreach ($lifestyleImages as $image)
                                <button
                                    type="button"
                                    wire:click="$dispatch('review-image', { src: @js($image['src']), original: @js($image['original']), title: @js($image['label']) })"
                                    class="overflow-hidden rounded-lg border border-slate-100 bg-slate-50 shadow-sm transition hover:border-emerald-300 hover:ring-2 hover:ring-emerald-100"
                                >
                                    <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}" loading="lazy" decoding="async" fetchpriority="low" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center px-4 text-center text-sm font-medium text-slate-400">
                            {{ $asset->redesign ? 'Bam Generate de tao lifestyle' : 'Cho anh master' }}
                        </div>
                    @endif
                </div>
            </div>

            <p class="mt-2 text-xs italic text-slate-500">Lifestyle co the upload, khong co thi bo qua.</p>
        </div>

        <div class="min-w-0 {{ $asset->redesign ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-orange-600">4. Mockup Tu Chon</x-label>
                @if ($asset->redesign && ! $asset->is_approved)
                    <button
                        type="button"
                        x-on:click="window.dispatchEvent(new CustomEvent('ornament-generation-started'))"
                        wire:click="generatePsdMockups"
                        wire:loading.attr="disabled"
                        wire:target="generatePsdMockups"
                        class="shrink-0 text-xs font-semibold text-orange-600 hover:text-orange-700 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="generatePsdMockups">✦ Generate + Update</span>
                        <span wire:loading wire:target="generatePsdMockups">Generating...</span>
                    </button>
                @endif
            </div>


            @php
                $psdMockups = collect(range(1, 11))
                    ->map(fn ($slot) => [
                        'slot' => $slot,
                        'src' => $asset->getAttribute("mockup{$slot}_preview_url"),
                        'original' => $asset->getAttribute("mockup{$slot}"),
                    ])
                    ->filter(fn ($mockup) => filled($mockup['original']));
            @endphp

            <div class="relative aspect-[4/4.45] overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                <div wire:loading.flex wire:target="generatePsdMockups" class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/95">
                    <x-spinner />
                </div>

                <div wire:loading.class="invisible" wire:target="generatePsdMockups" class="flex h-full min-h-0 flex-col">
                    <div class="mb-2 flex items-center justify-between gap-2 px-1">
                        <span class="text-xs font-bold uppercase text-slate-600">
                            {{ $psdMockups->count() }} MOCKUP
                        </span>

                        @if ($psdMockups->isNotEmpty())
                            <span class="text-[11px] font-medium text-slate-400">Scroll</span>
                        @endif
                    </div>

                    @if ($psdMockups->isNotEmpty())
                        <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($psdMockups as $mockup)
                                    <button
                                        type="button"
                                        wire:click="$dispatch('review-image', { src: @js($mockup['src']), original: @js($mockup['original']), title: @js('MOCKUP '.$mockup['slot']) })"
                                        class="aspect-[4/3] overflow-hidden rounded-lg border border-slate-100 bg-slate-50 shadow-sm transition hover:border-orange-300 hover:ring-2 hover:ring-orange-100"
                                    >
                                        <img src="{{ $mockup['src'] }}" alt="MOCKUP {{ $mockup['slot'] }}" loading="lazy" decoding="async" fetchpriority="low" class="h-full w-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex min-h-0 flex-1 items-center justify-center px-4 py-6 text-center text-sm font-medium text-slate-400">
                            {{ $asset->redesign ? 'Bam Generate PSD de tao mockup' : 'Cho anh master' }}
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="mt-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs text-slate-500">
                <div class="flex items-center justify-between gap-2">
                    <span class="min-w-0 truncate">
                        PSD: {{ $activePsdTemplateName ?? 'Chua chon PSD' }}
                    </span>
                    <button
                        type="button"
                        wire:click="$dispatch('openModal', { component: 'modals.ornament.psd-mockup-template' })"
                        class="shrink-0 font-semibold text-orange-600 hover:text-orange-700"
                    >
                        Chon PSD
                    </button>
                </div>
            </div>
        </div>
    </div>
</article>
