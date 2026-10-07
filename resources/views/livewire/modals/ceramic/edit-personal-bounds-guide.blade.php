<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/70 p-4" role="dialog" aria-modal="true" aria-labelledby="personal-bounds-title">
            <button type="button" class="fixed inset-0" wire:click="close" aria-label="Đóng cửa sổ bounds"></button>
            <form wire:submit.prevent="save" class="relative my-6 w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="personal-bounds-title" class="text-xl font-semibold">Bounds Ceramic của bạn</h2>
                        @if ($guideLink || $guideUrl)
                            <a href="{{ $guideLink ?: $guideUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-cyan-600 hover:text-cyan-500 dark:text-cyan-400 dark:hover:text-cyan-300">Hướng dẫn <span aria-hidden="true">↗</span></a>
                        @else
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Chưa có hướng dẫn từ Super Admin.</p>
                        @endif
                    </div>
                    <button type="button" wire:click="close" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Đóng">×</button>
                </div>
                @if ($currentUrl || $newUrl)
                    <div class="mt-4 grid grid-cols-1 items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                        <div>
                            <p class="mb-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Ảnh hiện tại</p>
                            @if ($currentUrl)
                                <img src="{{ $currentUrl }}" alt="Ảnh bounds hiện đang sử dụng" class="h-52 w-full rounded-lg border border-slate-200 object-contain dark:border-slate-700">
                            @else
                                <div class="flex h-52 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-500 dark:border-slate-700">Chưa có</div>
                            @endif
                        </div>
                        <span class="justify-self-center text-2xl font-semibold text-slate-400 sm:justify-self-auto" aria-hidden="true">→</span>
                        <div>
                            <p class="mb-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Ảnh mới</p>
                            @if ($newUrl)
                                <img src="{{ $newUrl }}" alt="Ảnh bounds mới đã chọn" class="h-52 w-full rounded-lg border border-cyan-500/70 object-contain dark:border-cyan-400/70">
                            @else
                                <div class="flex h-52 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-500 dark:border-slate-700">Chọn ảnh để xem trước</div>
                            @endif
                        </div>
                    </div>
                @endif
                <label for="personal-bounds-upload" class="mt-5 block text-sm font-semibold">Chọn ảnh bounds (PNG, JPG hoặc WebP; tối đa 10 MB)</label>
                <input id="personal-bounds-upload" type="file" wire:model="boundsGuide" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm dark:border-slate-700">
                @error('boundsGuide') <p role="alert" class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">Upload mới sẽ thay bounds riêng cũ; không thay ảnh của admin hay user khác.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">Hủy</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save,boundsGuide" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Lưu bounds</button>
                </div>
            </form>
        </div>
    @endif
</div>



