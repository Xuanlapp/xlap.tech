<div>
    @if ($isOpen)
        <div
            x-data
            x-on:keydown.escape.window="$wire.close()"
            tabindex="-1"
            aria-modal="true"
            role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-950/40 p-4"
        >
            <button type="button" class="fixed inset-0 cursor-default" wire:click="close" aria-label="Close delete idea confirmation"></button>

            <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                <h3 class="text-base font-bold text-slate-950">Xoa idea</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Ban co chac xoa idea:
                    <span class="font-semibold text-slate-950">{{ $keyword ?: 'Khong co keyword' }}</span>
                    trong trang
                    <span class="font-semibold text-slate-950">{{ $productLabel }}</span>
                    khong?
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        wire:click="close"
                        class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Khong
                    </button>
                    <button
                        type="button"
                        wire:click="deleteAsset"
                        wire:loading.attr="disabled"
                        wire:target="deleteAsset"
                        class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-rose-600 px-4 text-sm font-semibold text-black transition  disabled:opacity-60 hover:bg-red-700"
                    >
                        <span wire:loading.remove wire:target="deleteAsset">Ok</span>
                        <span wire:loading wire:target="deleteAsset">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
