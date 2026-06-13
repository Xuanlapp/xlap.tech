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
                    wire:click="$dispatch('openModal', { component: 'modals.ornament-amazon.edit-product-detail', arguments: { assetId: {{ $asset->id }} } })"
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

        <button
            type="button"
            wire:click="$dispatch('openModal', { component: 'modals.product-design.delete-idea-confirm', arguments: { productSlug: 'ornament', assetId: {{ $asset->id }}, keyword: @js($asset->keyword) } })"
            class="inline-flex h-8 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-bold text-rose-600 transition hover:border-rose-300 hover:bg-rose-100"
        >
            Delete
        </button>
    </div>

    <div class="grid gap-5 lg:grid-cols-4">
        <div class="min-w-0">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-slate-600">1. Source Image</x-label>
            </div>

            <x-image-preview reviewable class="aspect-[4/4.45] rounded-xl border border-slate-200 bg-slate-50" :src="$asset->image_preview_url" :original="$asset->image_link" alt="Source image" :asset-id="$asset->id" product-slug="ornament" :keyword="$asset->keyword">
                <span class="px-4 text-center text-sm font-medium text-slate-400">Dan link anh nguon vao day</span>
            </x-image-preview>

        </div>

        <div class="min-w-0 {{ $asset->image_link ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-blue-600">2. Create Master</x-label>
                @if ($asset->image_link && ! $asset->is_approved)
                    <x-ui.button color="blue" variant="ghost" size="xs" type="button" x-on:click="window.dispatchEvent(new CustomEvent('ornament-amazon-generation-started'))" wire:click="generateRedesign" wire:loading.attr="disabled" wire:target="generateRedesign" class="shrink-0">
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
                    <x-image-preview reviewable class="h-full w-full" :src="$asset->redesign_preview_url" :original="$asset->redesign" alt="Redesign image" :asset-id="$asset->id" product-slug="ornament" :keyword="$asset->keyword">
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
                    <x-ui.button color="emerald" variant="ghost" size="xs" type="button" x-on:click="window.dispatchEvent(new CustomEvent('ornament-amazon-generation-started'))" wire:click="generateFinalImages" wire:loading.attr="disabled" wire:target="generateFinalImages" class="shrink-0">
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
                                    wire:click="$dispatch('review-image', { src: @js($image['src']), original: @js($image['original']), title: @js($image['label']), productSlug: 'ornament', assetId: {{ $asset->id }}, keyword: @js($asset->keyword) })"
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

        </div>

        <div class="min-w-0 {{ $asset->redesign ? '' : 'opacity-55' }}">
            <div class="mb-2 flex h-5 items-center justify-between gap-2">
                <x-label class="truncate text-xs font-bold uppercase text-orange-600">4. Mockup Tu Chon</x-label>
                @if ($asset->redesign && ! $asset->is_approved)
                    <button
                        type="button"
                        x-on:click="window.dispatchEvent(new CustomEvent('ornament-amazon-generation-started'))"
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
                                        wire:click="$dispatch('review-image', { src: @js($mockup['src']), original: @js($mockup['original']), title: @js('MOCKUP '.$mockup['slot']), productSlug: 'ornament', assetId: {{ $asset->id }}, keyword: @js($asset->keyword) })"
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
            
        </div>
    </div>

    @php
        $sourceImages = collect([
            ['src' => $asset->image_preview_url, 'original' => $asset->image_link],
        ])
            ->merge(
                collect($asset->image_sub ?: [])
                    ->values()
                    ->map(fn ($image, $index) => [
                        'src' => $asset->image_sub_preview_urls[$index] ?? $image,
                        'original' => $image,
                    ])
            )
            ->filter(fn ($image) => filled($image['original']))
            ->unique('original')
            ->values();

        $listingData = $asset->data_item_add ?: [];
        $listingTitle = $listingData['productTitle'] ?? null;
        $listingLink = $listingData['link'] ?? null;
        $listingBullets = collect($listingData['bulletPoints'] ?? $listingData['bullets'] ?? [])->filter();
        $listingAplus = collect($listingData['aplus_text'] ?? $listingData['aplusText'] ?? [])->filter();
        $listingDescription = $listingData['productDescription'] ?? $listingData['description'] ?? null;
    @endphp

    @if ($sourceImages->count() > 1 || ! empty($listingData))
        <div class="mx-4 mb-5 mt-2 grid gap-4 lg:grid-cols-2">
            <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-1 flex items-baseline gap-3">
                    <span class="text-xs font-extrabold leading-none text-slate-200">{{ $sourceImages->count() }} imgs</span>
                </div>

                <div class="flex h-16 gap-3 overflow-x-auto pb-2">
                    @foreach ($sourceImages as $index => $image)
                        <button
                            type="button"
                            wire:click="$dispatch('review-image', { src: @js($image['src']), original: @js($image['original']), title: @js('Main image '.($index + 1)), productSlug: 'ornament', assetId: {{ $asset->id }}, keyword: @js($asset->keyword) })"
                            class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border {{ $index === 0 ? 'border-slate-400 ring-2 ring-slate-100' : 'border-slate-300' }} bg-slate-50 shadow-sm transition hover:border-blue-400 hover:ring-2 hover:ring-blue-100"
                        >
                            <img src="{{ $image['src'] }}" alt="Main image {{ $index + 1 }}" loading="lazy" decoding="async" fetchpriority="low" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="h-28 min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="h-full overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-2 text-xs leading-5 text-slate-700">
                    <div>
                        <div class="text-[10px] font-extrabold uppercase text-slate-500">PRODUCT TITLE:</div>
                        <p class="mt-1 font-semibold text-slate-950">{{ $listingTitle ?: $asset->keyword }}</p>
                    </div>

                    @if ($listingLink)
                        <div class="mt-3">
                            <div class="text-[10px] font-extrabold uppercase text-slate-500">LINK:</div>
                            <a href="{{ $listingLink }}" target="_blank" rel="noopener" class="mt-1 block break-all font-mono font-semibold text-blue-700 hover:text-blue-800">
                                {{ $listingLink }}
                            </a>
                        </div>
                    @endif

                    @if ($listingBullets->isNotEmpty())
                        <div class="mt-3">
                            <div class="text-[10px] font-extrabold uppercase text-slate-500">BULLET POINTS:</div>
                            <ol class="mt-1 list-decimal space-y-1 pl-4">
                                @foreach ($listingBullets as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @elseif ($listingDescription)
                        <div class="mt-3">
                            <div class="text-[10px] font-extrabold uppercase text-slate-500">PRODUCT DESCRIPTION:</div>
                            <p class="mt-1 whitespace-pre-line">{{ $listingDescription }}</p>
                        </div>
                    @endif

                    @if ($listingAplus->isNotEmpty())
                        <div class="mt-3">
                            <div class="text-[10px] font-extrabold uppercase text-slate-500">A+ / FAQ LIST:</div>
                            <ul class="mt-1 list-disc space-y-1 pl-4">
                                @foreach ($listingAplus as $text)
                                    <li>{{ $text }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif
</article>
