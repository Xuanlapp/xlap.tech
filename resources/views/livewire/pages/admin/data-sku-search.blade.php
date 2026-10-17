<section class="min-h-[calc(100vh-4rem)] bg-[#f4f6fb] px-4 py-6 text-slate-950 dark:bg-slate-950 dark:text-slate-100 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <header>
            <h1 class="text-3xl font-semibold tracking-tight">DATA</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Tra SKU tren tat ca user. Nhap nhieu SKU va ngan cach bang dau phay.</p>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label for="data-scope" class="mb-2 block text-sm font-semibold">Pham vi tim kiem</label>
                    <select id="data-scope" wire:model.live="filterMode" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="all">Tat ca user va product</option>
                        <option value="user">Theo user</option>
                        <option value="user_product">Theo user va product</option>
                        <option value="product">Theo product</option>
                    </select>
                </div>
                @if (in_array($filterMode, ['user', 'user_product'], true))
                    <div>
                        <label for="data-user" class="mb-2 block text-sm font-semibold">User</label>
                        <select id="data-user" wire:model.live="userId" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">Chon user</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (in_array($filterMode, ['product', 'user_product'], true))
                    <div>
                        <label for="data-product" class="mb-2 block text-sm font-semibold">Product</label>
                        <select id="data-product" wire:model.live="productId" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">Chon product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <label for="data-sku-search" class="mt-4 block text-sm font-semibold text-slate-800 dark:text-slate-100">SKU can tim</label>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input
                    id="data-sku-search"
                    type="text"
                    wire:model.live.debounce.300ms="skuSearch"
                    placeholder="Vi du: RGC8-1, RGC8-2"
                    class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-950 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 sm:flex-1"
                >
                <button type="button" wire:click="clearSearch" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Xoa loc</button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Co the nhap moi SKU tren mot dong hoac ngan cach bang dau phay. Ket qua bao gom tat ca user.</p>
        </section>

        @if ($skus === [])
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">Nhap it nhat mot SKU de bat dau tim.</div>
        @else
            @php
                $tableColumns = function ($assets, bool $showBulkReset) {
                    return view('livewire.pages.admin.partials.data-sku-results-table', compact('assets', 'showBulkReset'))->render();
                };
            @endphp
            <div class="space-y-6">
                @foreach ([['label' => 'Da duyet', 'assets' => $approvedAssets, 'showBulkReset' => true], ['label' => 'Chua duyet', 'assets' => $unapprovedAssets, 'showBulkReset' => false]] as $table)
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <div>
                                <h2 class="text-lg font-semibold">{{ $table['label'] }}</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ count($skus) }} SKU dang loc</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($table['showBulkReset'])
                                    <button type="button" wire:click="resetAllFilteredAssets" wire:confirm="Dua tat ca item da duyet dang loc ve chua duyet va xoa listing metadata? Cac anh van duoc giu nguyen." class="min-h-11 rounded-lg border border-amber-300/60 px-3 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-700/60 dark:text-amber-300 dark:hover:bg-amber-950/40">Chua duyet toan bo</button>
                                @endif
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">{{ $table['assets']->total() }} ket qua</span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold text-slate-600 dark:bg-slate-800/70 dark:text-slate-300"><tr><th class="px-5 py-3">SKU</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Product</th><th class="px-5 py-3">Status</th><th class="w-16 px-5 py-3 text-right" aria-label="Bo loc"></th></tr></thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @forelse ($table['assets'] as $asset)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50"><td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $asset->sku }}</td><td class="px-5 py-4">{{ $asset->user?->name ?: '-' }}</td><td class="whitespace-nowrap px-5 py-4">{{ $asset->product?->name ?: '-' }}</td><td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $asset->is_approved ? 'Da duyet' : 'Chua duyet' }}</span></td><td class="px-5 py-4 text-right"><div class="flex items-center justify-end gap-2">@if ($table['showBulkReset'])<button type="button" wire:click="resetAssetToUnapproved({{ $asset->id }})" wire:confirm="Dua item nay ve chua duyet va xoa listing metadata? Cac anh van duoc giu nguyen." title="Dua ve chua duyet" class="inline-flex min-h-11 rounded-lg border border-amber-300/60 px-3 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-700/60 dark:text-amber-300 dark:hover:bg-amber-950/40">Chua duyet</button>@endif<button type="button" wire:click="removeSku(@js($asset->sku))" aria-label="Bo {{ $asset->sku }} khoi bo loc" title="Bo SKU khoi bo loc" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-300"><span aria-hidden="true" class="text-lg leading-none">&times;</span></button></div></td></tr>
                                    @empty
                                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Khong co item nao trong nhom nay.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $table['assets']->links('vendor.pagination.idea-etsy') }}</div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</section>
