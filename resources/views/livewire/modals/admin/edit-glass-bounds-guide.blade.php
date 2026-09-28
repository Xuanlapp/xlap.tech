<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
            <button type="button" class="fixed inset-0 cursor-default" wire:click="close" aria-label="Close bounds guide modal"></button>
            <form wire:submit.prevent="save" class="relative my-6 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-cyan-600">Admin</p><h2 class="mt-1 text-xl font-bold">Ảnh bounds Glass</h2><p class="mt-1 text-sm text-slate-500">Một ảnh dùng chung cho phần 2. Create Master.</p></div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 hover:bg-slate-100" aria-label="Close">×</button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    @if ($currentUrl)<img src="{{ $currentUrl }}" alt="Current Glass bounds guide" class="mx-auto max-h-64 rounded-lg border border-slate-200 bg-slate-50 object-contain p-2">@else<div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có ảnh bounds dùng chung.</div>@endif
                    @if ($currentConfig)
                        <div class="grid grid-cols-3 gap-2 rounded-lg border border-cyan-200 bg-cyan-50 p-3 text-xs text-slate-700">
                            <div><span class="block text-slate-500">Canvas</span><strong>{{ data_get($currentConfig, 'canvas.width') }} × {{ data_get($currentConfig, 'canvas.height') }}</strong></div>
                            <div><span class="block text-slate-500">Template</span><strong>{{ data_get($currentConfig, 'target.width') }} × {{ data_get($currentConfig, 'target.height') }}</strong></div>
                            <div><span class="block text-slate-500">Safezone</span><strong>{{ data_get($currentConfig, 'safezone.width') && data_get($currentConfig, 'safezone.height') ? data_get($currentConfig, 'safezone.width').' × '.data_get($currentConfig, 'safezone.height') : 'Không dùng' }}</strong></div>
                        </div>
                    @endif
                    <label class="block"><span class="text-sm font-semibold text-slate-700">Chọn ảnh mới</span><input type="file" wire:model="boundsGuide" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm"></label>
                    @error('boundsGuide')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-500">App tự đọc vòng xanh nước làm Template. Safezone xanh lá là tùy chọn, không cần có vẫn lưu được. Upload ảnh mới là editor tự cập nhật, không cần sửa code. Tối đa 10 MB.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4"><button type="button" wire:click="close" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">No</button><button type="submit" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-bold text-white" wire:loading.attr="disabled"><span wire:loading.remove>Save</span><span wire:loading>Saving...</span></button></div>
            </form>
        </div>
    @endif
</div>
