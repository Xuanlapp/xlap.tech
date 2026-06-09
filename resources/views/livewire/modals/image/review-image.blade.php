<div>
    @if ($isOpen)
        <div
            x-data="{
                zoomed: false,
                dimensions: 'Loading...',
                generatedAt: new Date().toLocaleString(),
                copyImageUrl() {
                    if (! @js($original)) {
                        return;
                    }

                    navigator.clipboard?.writeText(@js($original));
                },
                fullscreenImage() {
                    if (this.$refs.previewImage?.requestFullscreen) {
                        this.$refs.previewImage.requestFullscreen();
                        return;
                    }

                    window.open(@js($original ?: $src), '_blank', 'noreferrer');
                },
            }"
            x-on:keydown.escape.window="$wire.close()"
            tabindex="-1"
            aria-modal="true"
            role="dialog"
            class="fixed inset-0 z-50 flex h-full w-full items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm"
        >
            <button type="button" class="fixed inset-0 cursor-default" wire:click="close" aria-label="Close image review"></button>

            <div class="relative z-10 w-full max-w-[1500px]">
                <div class="relative overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
                    <button
                        type="button"
                        wire:click="close"
                        class="absolute right-4 top-4 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/90 text-slate-500 shadow-sm backdrop-blur transition hover:bg-white hover:text-slate-950"
                        aria-label="Close image review"
                    >
                        <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>

                    <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,3fr)_minmax(360px,2fr)]">
                        <section class="min-w-0">
                            <div class="relative flex h-[min(76vh,820px)] min-h-[460px] items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-gray-400 shadow-[0_18px_50px_rgba(15,23,42,0.08)]">
                                @if (count($gallery) > 1)
                                    <div class="absolute left-5 top-5 z-10 rounded-xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm backdrop-blur">
                                        {{ $currentIndex + 1 }} / {{ count($gallery) }}
                                    </div>
                                @endif

                                <div class="absolute right-5 top-5 z-10 flex items-center gap-3">
                                    <button
                                        type="button"
                                        x-on:click="zoomed = ! zoomed"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:text-blue-700"
                                        aria-label="Zoom image"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="m21 21-4.35-4.35" />
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="M11 8v6M8 11h6" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="fullscreenImage()"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:text-blue-700"
                                        aria-label="Fullscreen image"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M16 21h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                                        </svg>
                                    </button>
                                </div>

                                @if (count($gallery) > 1)
                                    <button
                                        type="button"
                                        wire:click="previous"
                                        class="absolute left-5 top-1/2 z-10 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-900 shadow-lg transition hover:-translate-y-[52%] hover:bg-white hover:text-blue-700"
                                        aria-label="Previous image"
                                    >
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="m15 18-6-6 6-6" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="next"
                                        class="absolute right-5 top-1/2 z-10 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-900 shadow-lg transition hover:-translate-y-[52%] hover:bg-white hover:text-blue-700"
                                        aria-label="Next image"
                                    >
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </button>
                                @endif

                                @if ($src)
                                    <img
                                        x-ref="previewImage"
                                        x-bind:class="zoomed ? 'scale-110 cursor-zoom-out' : 'scale-100 cursor-zoom-in hover:scale-[1.035]'"
                                        x-on:click="zoomed = ! zoomed"
                                        x-on:load="dimensions = `${$event.target.naturalWidth} x ${$event.target.naturalHeight} px`"
                                        src="{{ $src }}"
                                        alt="{{ $title }}"
                                        class="max-h-[calc(100%-8rem)] max-w-[calc(100%-8rem)] object-contain drop-shadow-sm transition duration-300 ease-out"
                                    >
                                @else
                                    <div class="h-80 w-80 animate-pulse rounded-2xl bg-slate-200"></div>
                                @endif
                                <div class="absolute bottom-5 left-5 right-5 rounded-2xl border border-slate-200 bg-white/92 p-4 shadow-sm backdrop-blur">
                                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-center">
                                        <!-- <div class="flex items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path d="M12 7v5l3 2" />
                                                </svg>
                                            </span>
                                            <div>
                                                <div class="text-xs font-medium text-slate-500">Generated on</div>
                                                <div class="text-sm font-semibold text-slate-800" x-text="generatedAt"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z" />
                                                </svg>
                                            </span>
                                            <div>
                                                <div class="text-xs font-medium text-slate-500">Dimensions</div>
                                                <div class="text-sm font-semibold text-slate-800" x-text="dimensions"></div>
                                            </div>
                                        </div> -->
                                        <div class="flex flex-wrap gap-2">
                                            @if ($original)
                                                <a href="{{ $original }}" download class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M12 3v12M7 10l5 5 5-5M5 21h14" />
                                                    </svg>
                                                    Download
                                                </a>
                                            @endif
                                           
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <aside class="flex max-h-[76vh] min-h-[460px] min-w-0 flex-col gap-4 overflow-y-auto pr-1">
                            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M4 5a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                                        </svg>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-950">Image Information</h3>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Master Name</div>
                                        <div class="mt-1 text-base font-bold text-slate-950">{{ $title ?: 'Create Master 1' }}</div>
                                    </div>

                                    @if ($original)
                                        <div>
                                            <div class="text-xs font-medium text-slate-500">Source Image</div>
                                            <div class="mt-2 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                                <div class="min-w-0 flex-1 truncate text-sm text-slate-600">{{ $original }}</div>
                                                <button type="button" x-on:click="copyImageUrl()" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Copy image URL">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" />
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Status</div>
                                        <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5" />
                                            </svg>
                                            Generated Successfully
                                        </div>
                                    </div>
                                </div>
                            </section>

                            @if (! empty($listingInfo))
                                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="mb-4 flex items-center gap-3">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z" />
                                                <path d="M8 7h8M8 11h8M8 15h5" />
                                            </svg>
                                        </span>
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-950">Listing Information</h3>
                                            <p class="mt-0.5 text-xs font-medium text-slate-500">Da duyet</p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach ($listingInfo as $field)
                                            @php
                                                $value = $field['value'];
                                                $isLong = mb_strlen($value) > 100;
                                                $preview = $isLong ? mb_substr($value, 0, 100).'...' : $value;
                                            @endphp
                                            <div
                                                x-data="{ expanded: false }"
                                                class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                                            >
                                                <div class="mb-1 text-xs font-bold uppercase text-slate-500">{{ $field['label'] }}</div>
                                                <p class="whitespace-pre-line break-words text-sm leading-6 text-slate-800" x-text="expanded ? @js($value) : @js($preview)"></p>

                                                @if ($isLong)
                                                    <button
                                                        type="button"
                                                        x-on:click="expanded = ! expanded"
                                                        class="mt-2 text-xs font-bold text-blue-600 transition hover:text-blue-700"
                                                        x-text="expanded ? 'Thu gon' : 'Xem tiep'"
                                                    ></button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($action === 'sticker-redesign')
                                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="mb-4 flex items-center gap-3">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M12 2 14.7 9.3 22 12l-7.3 2.7L12 22l-2.7-7.3L2 12l7.3-2.7Z" />
                                            </svg>
                                        </span>
                                        <h3 class="text-sm font-bold text-slate-950">Custom Prompt</h3>
                                    </div>

                                    <form wire:submit.prevent="customizeStickerRedesign" class="space-y-3">
                                        <textarea
                                            x-ref="customPromptInput"
                                            wire:model.defer="customPrompt"
                                            required
                                            rows="5"
                                            maxlength="4000"
                                            class="block min-h-[120px] w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-inner shadow-slate-100 transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Describe the changes you want AI to make..."
                                        ></textarea>
                                        <div class="text-xs font-medium text-slate-500">
                                            <span x-text="$refs.customPromptInput?.value.length || 0">0</span> / 4000
                                        </div>
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="customizeStickerRedesign"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-500/25 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M12 2 14.7 9.3 22 12l-7.3 2.7L12 22l-2.7-7.3L2 12l7.3-2.7Z" />
                                            </svg>
                                            <span wire:loading.remove wire:target="customizeStickerRedesign">Generate Variation</span>
                                            <span wire:loading wire:target="customizeStickerRedesign">Generating...</span>
                                        </button>
                                    </form>
                                </section>

                                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="mb-4 flex items-center gap-3">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="m13 2-8 12h6l-2 8 9-13h-6Z" />
                                            </svg>
                                        </span>
                                        <h3 class="text-sm font-bold text-slate-950">Quick Actions</h3>
                                    </div>

                                    <div class="space-y-3">
                                        <button type="button" wire:click="selectAsStickerRedesign" class="group flex w-full items-center gap-4 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-md">
                                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M20 6 9 17l-5-5" />
                                                </svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold text-emerald-800">Select as Design #2</span>
                                                <span class="mt-0.5 block text-xs font-medium text-emerald-700/80">Use this design for your product</span>
                                            </span>
                                            <svg class="h-5 w-5 shrink-0 text-emerald-700 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </button>

                                        <button type="button" wire:click="createStickerItemFromImage" class="group flex w-full items-center gap-4 rounded-xl border border-violet-200 bg-violet-50/70 p-4 text-left transition hover:-translate-y-0.5 hover:border-violet-300 hover:bg-violet-50 hover:shadow-md">
                                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-violet-600 shadow-sm">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M12 5v14M5 12h14" />
                                                </svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold text-violet-800">Create New Variation</span>
                                                <span class="mt-0.5 block text-xs font-medium text-violet-700/80">Generate another variation from this</span>
                                            </span>
                                            <svg class="h-5 w-5 shrink-0 text-violet-700 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </button>

                                        @if ($original)
                                            <a href="{{ $original }}" target="_blank" rel="noreferrer" class="group flex w-full items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md">
                                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 shadow-sm">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M15 3h6v6M10 14 21 3M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-bold text-slate-900">Open Original Image</span>
                                                    <span class="mt-0.5 block text-xs font-medium text-slate-500">View the original source image</span>
                                                </span>
                                                <svg class="h-5 w-5 shrink-0 text-slate-500 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="m9 18 6-6-6-6" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </section>
                            @endif

                            <div class="sticky bottom-0 mt-auto rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg shadow-slate-900/5 backdrop-blur">
                                <div class="flex justify-end gap-3">
                                    <button type="button" wire:click="close" class="inline-flex min-w-32 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Close
                                    </button>
                                    @if ($action === 'sticker-redesign')
                                        <button type="button" wire:click="selectAsStickerRedesign" class="inline-flex min-w-40 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5" />
                                            </svg>
                                            Save Selection
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
