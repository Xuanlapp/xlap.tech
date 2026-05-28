<div>
    @if ($isOpen)
        <div
            x-data
            x-on:keydown.escape.window="$wire.close()"
            tabindex="-1"
            aria-modal="true"
            role="dialog"
            class="fixed inset-0 z-50 flex h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 md:inset-0"
        >
            <button type="button" class="fixed inset-0 cursor-default" wire:click="close" aria-label="Close image review"></button>

            <div class="relative z-10 max-h-full w-full max-w-5xl">
                <div class="relative rounded-lg bg-white shadow-sm dark:bg-gray-700">
                    <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 md:p-5 dark:border-gray-600">
                        <div class="min-w-0">
                            <h3 class="truncate text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $title }}
                            </h3>
                            @if ($original)
                                <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">{{ $original }}</p>
                            @endif
                        </div>

                        <button
                            type="button"
                            wire:click="close"
                            class="ms-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                        >
                            <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>

                    <div class="space-y-4 p-4 md:p-5">
                        <div class="flex max-h-[72vh] min-h-80 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                            @if ($src)
                                <img src="{{ $src }}" alt="{{ $title }}" class="max-h-[72vh] max-w-full object-contain">
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 rounded-b border-t border-gray-200 p-4 md:p-5 dark:border-gray-600">
                        @if ($original)
                            <x-ui.button href="{{ $original }}" color="blue" target="_blank" rel="noreferrer">
                                Mở ảnh gốc
                            </x-ui.button>
                        @endif

                        <x-ui.button color="light" type="button" wire:click="close">
                            Đóng
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
