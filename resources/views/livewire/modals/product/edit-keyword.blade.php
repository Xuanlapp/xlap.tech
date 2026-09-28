<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
            <button type="button" class="fixed inset-0 cursor-default" wire:click="close" aria-label="Close"></button>
            <form wire:submit.prevent="save" class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Edit item</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950">Edit Keyword</h2>
                    <p class="mt-1 text-sm text-slate-500">Chỉ thay đổi keyword của item chưa duyệt.</p>
                </div>
                <div class="px-6 py-5">
                    <label for="edit-product-keyword" class="block text-sm font-semibold text-slate-700">Keyword</label>
                    <textarea id="edit-product-keyword" wire:model="keyword" rows="4" maxlength="255" autofocus class="mt-2 block w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error('keyword') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" wire:click="close" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Close</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-60"><span wire:loading.remove>Save</span><span wire:loading>Saving...</span></button>
                </div>
            </form>
        </div>
    @endif
</div>
