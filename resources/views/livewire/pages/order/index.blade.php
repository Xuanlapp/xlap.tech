<section class="min-h-[calc(100vh-4rem)] bg-[#f4f6fb] px-3 py-6 text-slate-950 sm:px-5 lg:px-8">
    <div class="mx-auto flex w-full max-w-[1450px] flex-col gap-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-orange-600">Order</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Order workspace</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500">Tra cuu SKU va anh Create Master tren Google Drive. Du lieu len don se duoc xu ly bang import report.</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3 text-right text-sm font-semibold text-slate-600">
                    <span class="block text-2xl font-black text-slate-950">{{ $items->total() }}</span>
                    SKU san sang
                </div>
            </div>
        </div>

        @if ($message)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700">{{ $message }}</div>
        @endif
        @if ($error)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700">{{ $error }}</div>
        @endif

        <div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div><h2 class="text-base font-black text-slate-950">SKU Order Items</h2><p class="mt-1 text-xs text-slate-500">Anh trong danh sach la link Google Drive.</p></div>
                    <div class="flex items-center gap-2">
                        @if (auth()->user()?->is_admin)
                            <button type="button" wire:click="openImportModal" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white hover:bg-orange-600">Import Order Item</button>
                            <button type="button" wire:click="reloadItems" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:border-orange-400 hover:text-orange-700 disabled:opacity-60">
                                <span wire:loading.remove wire:target="reloadItems">Reload items</span>
                                <span wire:loading wire:target="reloadItems">Dang nap...</span>
                            </button>
                        @endif
                        @if (count($selectedSkuOrderItemIds) > 0)<button type="button" wire:click="openManualFbaModal" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">Len don FBA ({{ count($selectedSkuOrderItemIds) }})</button>@endif<button type="button" wire:click="openOrderReportImportModal" class="rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white hover:bg-orange-700">Import Order Report</button>
                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">{{ $items->total() }} items</span>
                    </div>
                </div>
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row">
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Tim theo SKU..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm sm:max-w-xs">
                    <select wire:model.live="productFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Tat ca trang</option>@foreach ($products as $product)<option value="{{ $product->slug }}">{{ $product->name }}</option>@endforeach</select>
                    @if ($items->isNotEmpty())<button type="button" wire:click="toggleCurrentSkuPageSelection(@js($items->pluck('id')->all()))" class="rounded-lg border border-blue-300 px-3 py-2 text-xs font-bold text-blue-700">Chon/bo chon trang nay ({{ $items->count() }})</button>@endif<select wire:model.live="perPage" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="5">5 / trang</option><option value="10">10 / trang</option><option value="20">20 / trang</option><option value="50">50 / trang</option><option value="100">100 / trang</option></select>
                </div>
                @if ($items->isEmpty())
                    <div class="px-5 py-16 text-center text-sm font-medium text-slate-400">Chua co SKU nao san sang. Hay duyet item va cho Drive upload xong.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Chon</th><th class="px-5 py-3">SKU</th><th class="px-5 py-3">Product</th><th class="px-5 py-3">Images</th><th class="px-5 py-3">Link Images</th><th class="px-5 py-3">Source</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($items as $item)
                                    <tr wire:key="order-item-{{ $item->id }}" class="hover:bg-orange-50/40">
                                        <td class="px-5 py-3"><input type="checkbox" wire:model.live="selectedSkuOrderItemIds" value="{{ $item->id }}" class="rounded border-slate-300"></td>
                                        <td class="px-5 py-3 font-black text-slate-950">@if (auth()->user()?->is_admin)<button type="button" wire:click="openEditOrderItemModal({{ $item->id }})" class="text-left hover:text-orange-600 hover:underline" title="Sua SKU Order Item">{{ $item->sku }}</button>@else{{ $item->sku }}@endif</td>
                                        <td class="px-5 py-3 text-slate-600"><span class="block font-semibold text-slate-800">{{ $item->product?->display_name ?? $item->asset?->product?->display_name ?? 'Chua gan product' }}</span><span class="block max-w-[240px] truncate text-xs text-slate-500">{{ $item->asset?->keyword }}</span></td>
                                        <td class="px-5 py-3">
                                            <button type="button" x-on:click="$dispatch('review-image', { src: @js($item->image_preview_url), original: @js($item->image_link), title: @js('Create Master - '.$item->sku), productSlug: @js($item->asset?->product?->slug), assetId: @js($item->asset?->id), keyword: @js($item->asset?->keyword), imageOnly: true })" class="block rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400" title="Xem anh lon">
                                                <img src="{{ $item->image_preview_url }}" alt="{{ $item->sku }}" loading="lazy" class="h-14 w-14 rounded-lg border border-slate-200 bg-slate-50 object-cover transition hover:scale-105">
                                            </button>
                                        </td>
                                        <td class="max-w-[280px] px-5 py-3"><a href="{{ $item->image_link }}" target="_blank" rel="noopener" class="block truncate text-xs font-semibold text-blue-600 hover:underline">{{ $item->image_link }}</a></td>
                                        <td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $item->source === 'manual_import' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $item->source === 'manual_import' ? 'Manual import' : 'Asset sync' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $items->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
        </div>
        <div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div><h2 class="text-base font-black text-slate-950">History Orders</h2><p class="mt-1 text-xs text-slate-500">Cac don da len va da duoc ghi nhan de tranh trung order.</p></div>
                    <div class="flex items-center gap-2"><input type="search" wire:model.live.debounce.400ms="historyOrderSearch" placeholder="Search order ID..." class="w-44 rounded-lg border border-slate-300 px-3 py-2 text-xs">@if (count($selectedHistoryOrderIds) > 0)<button type="button" wire:click="exportSelectedHistoryOrders" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Export ({{ count($selectedHistoryOrderIds) }})</button>@endif<span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">{{ $historyOrders->total() }} orders</span></div>
                </div>
                @if ($historyOrders->isEmpty())
                    <div class="px-5 py-12 text-center text-sm font-medium text-slate-400">Chua co order nao trong history.</div>
                @else
                    <div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Select</th><th class="px-5 py-3">Order ID</th>@if (auth()->user()?->is_admin)<th class="px-5 py-3">User</th>@endif<th class="px-5 py-3">Qty</th><th class="px-5 py-3">Size</th><th class="px-5 py-3">Preview</th><th class="px-5 py-3">Images</th><th class="px-5 py-3">Order Product ID</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($historyOrders as $historyOrder)@php($historyData = $historyOrder->report_data ?? [])<tr wire:key="history-order-{{ $historyOrder->id }}" class="hover:bg-orange-50/40"><td class="px-5 py-3"><input type="checkbox" wire:model.live="selectedHistoryOrderIds" value="{{ $historyOrder->id }}" class="rounded border-slate-300"></td><td class="px-5 py-3 font-black text-slate-950">{{ $historyOrder->order_id }}</td>@if (auth()->user()?->is_admin)<td class="px-5 py-3 text-slate-700"><span class="block font-semibold">{{ $historyOrder->user?->name ?? 'Deleted user' }}</span><span class="block text-xs text-slate-500">{{ $historyOrder->user?->email }}</span></td>@endif<td class="px-5 py-3">{{ $historyData['quantity'] ?? '-' }}</td><td class="px-5 py-3">{{ $historyData['size'] ?? '-' }}</td><td class="px-5 py-3">@if ($historyOrder->image_preview_url)<button type="button" x-on:click="$dispatch('review-image', { src: @js($historyOrder->image_preview_url), original: @js(($historyOrder->images_link ?? [])[0] ?? ''), title: @js('Order ' . $historyOrder->order_id), imageOnly: true })"><img src="{{ $historyOrder->image_preview_url }}" alt="Order preview" loading="lazy" class="h-12 w-12 rounded border object-cover"></button>@else-@endif</td><td class="px-5 py-3"><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">{{ count($historyOrder->images_link ?? []) }} images</span></td><td class="px-5 py-3 font-black text-slate-950">{{ $historyData['product_id'] ?? '-' }}</td></tr>@endforeach</tbody></table></div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $historyOrders->links(data: ['scrollTo' => false]) }}</div>
                @endif
            </div>
        </div>
        <div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div><h2 class="text-base font-black text-slate-950">Order Products</h2><p class="mt-1 text-xs text-slate-500">Danh muc product fulfillment dung de doi chieu va len don.</p></div>
                    <div class="flex items-center gap-2">
                        @if (auth()->user()?->is_admin)<button type="button" wire:click="openOrderProductImportModal" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white hover:bg-orange-600">Import Order Product</button>@endif
                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">{{ $orderProducts->total() }} products</span>
                    </div>
                </div>
                @if ($orderProducts->isEmpty())
                    <div class="px-5 py-12 text-center text-sm font-medium text-slate-400">Chua co Order Product. Admin hay import file Excel/CSV.</div>
                @else
                    <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">ID</th><th class="px-5 py-3">FBM/FBA</th><th class="px-5 py-3">Order Product ID</th><th class="px-5 py-3">Product Name</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($orderProducts as $orderProduct)<tr wire:key="order-product-{{ $orderProduct->id }}" class="hover:bg-orange-50/40"><td class="px-5 py-3 font-semibold text-slate-700">{{ $orderProduct->source_id }}</td><td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $orderProduct->fulfillment_type === 'FBA' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">{{ $orderProduct->fulfillment_type }}</span></td><td class="px-5 py-3 font-black text-slate-950">{{ $orderProduct->order_product_id }}</td><td class="px-5 py-3 text-slate-700">{{ $orderProduct->product_name }}</td></tr>@endforeach</tbody></table></div>
                    <div class="border-t border-slate-200 px-5 py-4">{{ $orderProducts->links(data: ['scrollTo' => false]) }}</div>
                @endif
            </div>
        </div>
        @if ($showManualFbaModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"><div class="flex max-h-[92vh] w-full max-w-5xl flex-col rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-start justify-between"><div><h2 class="text-lg font-black">Len don FBA bang tay</h2><p class="mt-1 text-sm text-slate-500">Nhap Size (in) va Pack de tim Product ID FBA, sau do export cac dong hop le.</p></div><button type="button" wire:click="closeManualFbaModal" class="text-2xl text-slate-400">&times;</button></div><div class="mt-4 grid gap-3 sm:grid-cols-4"><div><label class="mb-1 block text-xs font-bold text-slate-600">Size (in)</label><input type="number" min="1" step="0.1" wire:model.live.debounce.400ms="manualFbaSize" placeholder="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-bold text-slate-600">Pack</label><input type="number" min="1" wire:model.live.debounce.400ms="manualFbaPack" placeholder="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-bold text-slate-600">Quantity mac dinh</label><input type="number" min="1" wire:model.live.debounce.400ms="manualFbaQuantity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-bold text-slate-600">Product</label><div class="max-h-10 overflow-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ collect($manualFbaPreviewRows)->pluck('product_name')->filter()->unique()->implode(' | ') ?: '-' }}</div></div></div><div class="mt-4 max-h-[480px] overflow-auto rounded-lg border"><table class="w-full min-w-[700px] text-left text-sm"><thead class="sticky top-0 bg-emerald-500 text-white"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Product ID</th><th class="px-3 py-2">Quantity</th><th class="px-3 py-2">Link Design</th><th class="px-3 py-2">Status</th></tr></thead><tbody>@foreach ($manualFbaPreviewRows as $row)<tr class="{{ $row['error'] !== '' ? 'bg-rose-50' : '' }}"><td class="px-3 py-2 font-bold">{{ $row['sku'] }}</td><td class="px-3 py-2 font-bold">{{ $row['product_id'] ?: '-' }}</td><td class="px-3 py-2"><input type="number" min="1" wire:model.live="manualFbaPreviewRows.{{ $loop->index }}.quantity" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm"></td><td class="max-w-72 truncate px-3 py-2"><a href="{{ $row['link_design'] }}" target="_blank" class="text-blue-600">{{ $row['link_design'] }}</a></td><td class="px-3 py-2 {{ $row['error'] !== '' ? 'text-rose-700' : 'text-emerald-700' }}">{{ $row['error'] ?: 'Ready' }}</td></tr>@endforeach</tbody></table></div><div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="closeManualFbaModal" class="rounded-lg border px-4 py-2 text-sm font-bold">Close</button>@if (collect($manualFbaPreviewRows)->contains(fn ($row) => $row['error'] === ''))<button type="button" wire:click="exportManualFbaOrders" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">OK & Tai Excel</button>@endif</div></div></div>
        @endif        @if ($showImportModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Import Order Item</h2><p class="mt-1 text-sm text-slate-500">File CSV/XLSX can co cot <strong>SKU</strong> va <strong>IMAGES_LINK</strong> (Google Drive).</p></div><button type="button" wire:click="closeImportModal" class="text-2xl text-slate-400">&times;</button></div>
                    <form wire:submit="importOrderItems" class="mt-5 space-y-4">
                        <select wire:model="importUserId" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"><option value="">Chon user su dung account</option>@foreach ($importUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>@endforeach</select>@error('importUserId')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <select wire:model="importProductId" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"><option value="">Chon Product</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->display_name }}</option>@endforeach</select>@error('importProductId')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <input type="file" wire:model="orderItemFile" accept=".csv,.txt,.xlsx" class="block w-full rounded-lg border border-dashed border-blue-400 p-5 text-sm">@error('orderItemFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        @if ($previewRows)
                            <div class="max-h-64 overflow-auto rounded-lg border border-slate-200"><table class="w-full text-left text-xs"><thead class="sticky top-0 bg-slate-100 text-slate-600"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">IMAGES_LINK</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($previewRows as $row)<tr><td class="px-3 py-2 font-bold">{{ $row['sku'] }}</td><td class="max-w-[280px] truncate px-3 py-2 text-blue-600">{{ $row['image_link'] }}</td></tr>@endforeach</tbody></table></div><p class="text-xs font-semibold text-emerald-600">Preview {{ count($previewRows) }} dong. Hay kiem tra truoc khi Import.</p>
                        @endif
                        <div class="flex justify-end gap-2"><button type="button" wire:click="closeImportModal" class="rounded-lg border px-4 py-2 text-sm font-bold">Huy</button><button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white">Import</button></div>
                    </form>
                </div>
            </div>
        @endif
        @if ($showEditOrderItemModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Edit SKU Order Item</h2><p class="mt-1 text-sm text-slate-500">Cap nhat Product va Link Images cho SKU nay.</p></div><button type="button" wire:click="closeEditOrderItemModal" class="text-2xl text-slate-400">&times;</button></div>
                    <form wire:submit="updateOrderItem" class="mt-5 space-y-4">
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Quantity mac dinh</label><input type="number" min="1" wire:model.live.debounce.400ms="manualFbaQuantity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-bold text-slate-600">Product</label><select wire:model="editingOrderItemProductId" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"><option value="">Chon Product</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->display_name }}</option>@endforeach</select>@error('editingOrderItemProductId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-600">Link Images</label><input type="url" wire:model="editingOrderItemImageLink" placeholder="https://drive.google.com/..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5">@error('editingOrderItemImageLink')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class="flex justify-end gap-2"><button type="button" wire:click="closeEditOrderItemModal" class="rounded-lg border px-4 py-2 text-sm font-bold">Huy</button><button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white">Luu thay doi</button></div>
                    </form>
                </div>
            </div>
        @endif
        @if ($showOrderReportImportModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="flex max-h-[95vh] w-full max-w-[92vw] flex-col rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Import Order Report</h2><p class="mt-1 text-sm text-slate-500">Upload report don hang de xem truoc. Hien tai he thong chua xu ly hay tao don; ban se cung cap quy tac xu ly o buoc sau.</p></div><button type="button" wire:click="closeOrderReportImportModal" class="text-2xl text-slate-400">&times;</button></div>
                    <div class="mt-5 min-h-0 space-y-4 overflow-y-auto">
                        <input type="file" wire:model="orderReportFile" accept=".txt,.csv" class="block w-full rounded-lg border border-dashed border-orange-400 p-5 text-sm">@error('orderReportFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-500">Ho tro file TXT/CSV theo cot tab Amazon.</p>
                        @if ($orderReportPreviewRows)
                            <div class="mt-6 border-t border-slate-200 pt-5"><div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="text-base font-black text-slate-950">Preview template len don Amazon</h3><p class="mt-1 text-xs text-slate-500">Tu dong doi SKU, lay Link Design va tim Product ID theo FBM/FBA, Product, kich thuoc, Holo va tag.</p></div><div class="flex items-center gap-2"><label class="flex items-center gap-1.5 whitespace-nowrap text-xs font-bold text-slate-700"><input type="checkbox" wire:model.live="orderReportHolo" class="rounded border-slate-300"> Holo</label><input type="text" wire:model.live.debounce.400ms="orderReportProductTag" placeholder="Product tag: #HBGS" class="w-36 rounded-lg border border-slate-300 px-2 py-1.5 text-xs">@if ($orderReportFulfillment === 'FBA')<input type="number" min="1" wire:model.live.debounce.400ms="orderReportPack" placeholder="Pack: 3" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-xs">@endif<div class="flex rounded-lg border border-slate-300 p-1"><button type="button" wire:click="$set('orderReportFulfillment', 'FBM')" class="rounded-md px-3 py-1.5 text-xs font-bold {{ $orderReportFulfillment === 'FBM' ? 'bg-slate-950 text-white' : 'text-slate-600' }}">Len FBM</button><button type="button" wire:click="$set('orderReportFulfillment', 'FBA')" class="rounded-md px-3 py-1.5 text-xs font-bold {{ $orderReportFulfillment === 'FBA' ? 'bg-slate-950 text-white' : 'text-slate-600' }}">Len FBA</button></div></div></div>
                                <div class="mt-4 max-h-[360px] overflow-auto rounded-lg border border-slate-200"><table class="w-full min-w-[1350px] text-left text-xs"><thead class="sticky top-0 bg-emerald-500 text-white"><tr><th class="px-3 py-2">ID ORDER</th><th class="px-3 py-2">Product ID</th><th class="px-3 py-2">Quantity</th><th class="px-3 py-2">Link Design</th><th class="px-3 py-2">TO NAME</th><th class="px-3 py-2">TO ADDRESS 1</th><th class="px-3 py-2">TO ADDRESS 2</th><th class="px-3 py-2">TO CITY</th><th class="px-3 py-2">TO STATE</th><th class="px-3 py-2">TO POSTCODE</th><th class="px-3 py-2">TO COUNTRY</th><th class="px-3 py-2">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($orderFulfillmentPreviewRows as $row)<tr class="{{ $row['error'] !== '' ? 'bg-rose-50' : '' }}"><td class="px-3 py-2 font-bold">{{ $row['id_order'] }}</td><td class="px-3 py-2 font-bold">{{ $row['product_id'] ?: '-' }}</td><td class="px-3 py-2"><input type="number" min="1" wire:model.live="manualFbaPreviewRows.{{ $loop->index }}.quantity" class="w-20 rounded border border-slate-300 px-2 py-1 text-sm"></td><td class="max-w-[250px] truncate px-3 py-2"><a href="{{ $row['link_design'] }}" target="_blank" class="text-blue-600 hover:underline">{{ $row['link_design'] ?: '-' }}</a></td><td class="px-3 py-2">{{ $row['to_name'] }}</td><td class="px-3 py-2">{{ $row['to_address_1'] }}</td><td class="px-3 py-2">{{ $row['to_address_2'] }}</td><td class="px-3 py-2">{{ $row['to_city'] }}</td><td class="px-3 py-2">{{ $row['to_state'] }}</td><td class="px-3 py-2">{{ $row['to_postcode'] }}</td><td class="px-3 py-2">{{ $row['to_country'] }}</td><td class="px-3 py-2 font-semibold {{ $row['error'] !== '' ? 'text-rose-700' : 'text-emerald-700' }}">{{ $row['error'] ?: 'Ready' }}</td></tr>@endforeach</tbody></table></div></div>
                        @endif
                        @php($errorRows = collect($orderFulfillmentPreviewRows)->filter(fn ($row) => ($row['error'] ?? '') !== ''))
                        @php($validRows = collect($orderFulfillmentPreviewRows)->filter(fn ($row) => ($row['error'] ?? '') === ''))
                        @if ($errorRows->isNotEmpty())<div class="mt-3 max-h-28 overflow-auto rounded border border-rose-200 bg-rose-50 p-2 text-xs text-rose-700"><strong>Dong loi ({{ $errorRows->count() }}):</strong> @foreach ($errorRows as $row)<span class="mr-3">{{ $row['id_order'] }} - {{ $row['error'] }}</span>@endforeach</div>@endif
                        <div class="flex justify-end gap-2"><button type="button" wire:click="closeOrderReportImportModal" class="rounded-lg border px-4 py-2 text-sm font-bold">Close</button>@if ($validRows->isNotEmpty())<button type="button" wire:click="confirmOrderReport" wire:loading.attr="disabled" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">OK & Tai Excel</button>@endif</div>
                    </div>
                </div>
            </div>
        @endif
        @if ($showOrderProductImportModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Import Order Product</h2><p class="mt-1 text-sm text-slate-500">File CSV/XLSX can co cot <strong>ID</strong>, <strong>FBM/FBA</strong>, <strong>order_product_id</strong>, <strong>Product_Name</strong>.</p></div><button type="button" wire:click="closeOrderProductImportModal" class="text-2xl text-slate-400">&times;</button></div>
                    <form wire:submit="importOrderProducts" class="mt-5 space-y-4">
                        <input type="file" wire:model="orderProductFile" accept=".csv,.txt,.xlsx" class="block w-full rounded-lg border border-dashed border-blue-400 p-5 text-sm">@error('orderProductFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        @if ($orderProductPreviewRows)
                            <div class="max-h-72 overflow-auto rounded-lg border border-slate-200"><table class="w-full min-w-[650px] text-left text-xs"><thead class="sticky top-0 bg-slate-100 text-slate-600"><tr><th class="px-3 py-2">ID</th><th class="px-3 py-2">FBM/FBA</th><th class="px-3 py-2">Order Product ID</th><th class="px-3 py-2">Product Name</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($orderProductPreviewRows as $row)<tr><td class="px-3 py-2">{{ $row['source_id'] }}</td><td class="px-3 py-2">{{ $row['fulfillment_type'] }}</td><td class="px-3 py-2 font-bold">{{ $row['order_product_id'] }}</td><td class="px-3 py-2">{{ $row['product_name'] }}</td></tr>@endforeach</tbody></table></div><p class="text-xs font-semibold text-emerald-600">Preview {{ count($orderProductPreviewRows) }} dong. Du lieu trung ID se duoc cap nhat.</p>
                        @endif
                        <div class="flex justify-end gap-2"><button type="button" wire:click="closeOrderProductImportModal" class="rounded-lg border px-4 py-2 text-sm font-bold">Huy</button><button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white">Import</button></div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>
