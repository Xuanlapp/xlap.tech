<section class="min-h-[calc(100vh-4rem)] bg-[#f4f6fb] px-3 py-6 text-slate-950 sm:px-5 lg:px-8">
    <div class="mx-auto w-full max-w-[1450px] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Order Amazon Paste</h1>
            <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                @if (auth()->user()?->isSuperAdmin())
                    <button type="button" x-on:click="$dispatch('open-modal', 'order-amazon-bounds-template')" class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">Template bounds</button>
                    <button type="button" x-on:click="$dispatch('open-modal', 'order-amazon-model')" class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">Model</button>
                @endif
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">CheapKeyAI: @if ($cheapKeyAiBalance === null) null @elseif ($cheapKeyAiBalance['ok'] ?? false) ${{ number_format((float) $cheapKeyAiBalance['balance'], 3, '.', '') }} @else N/A @endif</span>
                <button type="button" wire:click="$dispatch('openModal', { component: 'modals.ai.change-cheap-key-ai-key', arguments: { functionKey: 'order-amazon-paste' } })" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm">Thêm hoặc đổi API CheapKeyAI</button>
                @if (auth()->user()?->isSuperAdmin())
                    <button type="button" x-on:click="$dispatch('open-modal', 'order-amazon-prompt')" class="inline-flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">Prompt</button>
                @endif
            </div>
        </div>
        @php
            $missingBoundsTemplates = array_diff_key(['glass' => 'Glass', 'ceramic' => 'Ceramic'], $orderAmazonBoundsTemplates);
        @endphp
        @if (auth()->user()?->isSuperAdmin() && $missingBoundsTemplates)
            <p role="status" class="mt-3 text-sm text-amber-700 dark:text-amber-300">
                Chưa có template: {{ implode(', ', $missingBoundsTemplates) }}.
            </p>
        @endif
        @include('livewire.pages.order.partials.order-template-editor')
        <livewire:modals.ai.change-cheap-key-ai-key function-key="order-amazon-paste" />
        @if (auth()->user()?->isSuperAdmin())
            <x-modal name="order-amazon-bounds-template" maxWidth="2xl" :centered="true" focusable>
                <div role="dialog" aria-modal="true" aria-labelledby="order-amazon-bounds-template-title" class="bg-white p-5 text-slate-950 dark:bg-slate-900 dark:text-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="order-amazon-bounds-template-title" class="text-lg font-semibold">Template bounds Order</h2>
                        <button type="button" wire:loading.attr="disabled" wire:target="orderAmazonBoundsTemplate,saveOrderAmazonBoundsTemplate" x-on:click="$dispatch('close-modal', 'order-amazon-bounds-template')" aria-label="Đóng" class="rounded-md px-3 py-2 text-sm">&times;</button>
                    </div>
                    <p class="mt-2 text-sm">Tải template riêng cho Glass hoặc Ceramic. Template được dùng chung cho tất cả user.</p>
                    <div class="mt-4 rounded-lg border border-slate-200 bg-[#0f1218] p-4 text-slate-100 dark:border-slate-700">
                        <h3 class="text-sm font-semibold">{{ ucfirst($orderAmazonBoundsType) }}</h3>
                        <div class="mt-3 grid grid-cols-[minmax(0,1fr)_28px_minmax(0,1fr)] items-center gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-300">Ảnh hiện tại</p>
                                <div class="mt-2 flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-950">
                                    @if (!empty($orderAmazonBoundsTemplates[$orderAmazonBoundsType]))
                                        <img src="{{ $orderAmazonBoundsTemplates[$orderAmazonBoundsType] }}" alt="Template {{ ucfirst($orderAmazonBoundsType) }} hiện tại" class="h-full w-full object-contain">
                                    @else
                                        <span class="px-3 text-center text-xs text-slate-400">Chưa có template</span>
                                    @endif
                                </div>
                            </div>
                            <span aria-hidden="true" class="text-center text-2xl text-slate-300">&#8594;</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-300">Ảnh mới</p>
                                <div class="mt-2 flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-600 bg-slate-950">
                                    @if ($orderAmazonBoundsTemplate && in_array($orderAmazonBoundsTemplate->getMimeType(), ['image/png', 'image/jpeg', 'image/webp'], true))
                                        <div wire:loading.remove wire:target="orderAmazonBoundsTemplate" class="h-full w-full"><img src="{{ $orderAmazonBoundsTemplate->temporaryUrl() }}" alt="Template {{ ucfirst($orderAmazonBoundsType) }} mới" class="h-full w-full object-contain"></div>
                                    @else
                                        <span wire:loading.remove wire:target="orderAmazonBoundsTemplate" class="px-3 text-center text-xs text-slate-400">Chọn ảnh để xem trước</span>
                                    @endif
                                    <span wire:loading wire:target="orderAmazonBoundsTemplate" role="status" class="px-3 text-center text-xs text-slate-300">Đang tải ảnh lên...</span>
                                </div>
                            </div>
                        </div>
                    </div>                    <label for="order-amazon-bounds-type" class="mt-4 block text-sm font-semibold">Loại template</label>
                    <select id="order-amazon-bounds-type" wire:model.live="orderAmazonBoundsType" wire:loading.attr="disabled" wire:target="orderAmazonBoundsTemplate,saveOrderAmazonBoundsTemplate" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        <option value="glass">Glass</option>
                        <option value="ceramic">Ceramic</option>
                    </select>
                    <label for="order-amazon-bounds-file" class="mt-4 block text-sm font-semibold">Ảnh template</label>
                    <input id="order-amazon-bounds-file" type="file" accept="image/png,image/jpeg,image/webp" wire:model="orderAmazonBoundsTemplate" wire:loading.attr="disabled" wire:target="orderAmazonBoundsTemplate,saveOrderAmazonBoundsTemplate" class="mt-2 w-full text-sm">
                    @error('orderAmazonBoundsTemplate') <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                    @error('orderAmazonBoundsType') <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" wire:loading.attr="disabled" wire:target="orderAmazonBoundsTemplate,saveOrderAmazonBoundsTemplate" x-on:click="$dispatch('close-modal', 'order-amazon-bounds-template')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Hủy</button>
                        <button type="button" wire:click="saveOrderAmazonBoundsTemplate" wire:loading.attr="disabled" wire:target="orderAmazonBoundsTemplate,saveOrderAmazonBoundsTemplate" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="saveOrderAmazonBoundsTemplate">Lưu template</span><span wire:loading.flex wire:target="saveOrderAmazonBoundsTemplate" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang lưu...</span></button>
                    </div>
                </div>
            </x-modal>
            <x-modal name="order-amazon-model" maxWidth="2xl" :centered="true" focusable>
                <div role="dialog" aria-modal="true" aria-labelledby="order-amazon-model-title" class="bg-white p-5 text-slate-950 dark:bg-slate-900 dark:text-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="order-amazon-model-title" class="text-lg font-semibold">Model tạo ảnh dùng chung</h2>
                        <button type="button" x-on:click="$dispatch('close-modal', 'order-amazon-model')" aria-label="Đóng" class="rounded-md px-3 py-2 text-sm">&times;</button>
                    </div>
                    <p class="mt-2 text-sm">Model được áp dụng cho các lần tạo ảnh mới của tất cả user.</p>
                    <label for="order-amazon-model-select" class="mt-4 block text-sm font-semibold">Model CheapKeyAI</label>
                    <select id="order-amazon-model-select" wire:model="orderAmazonImageModel" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                        @foreach ($orderAmazonImageModels as $model)
                            <option value="{{ $model }}">{{ $model }}</option>
                        @endforeach
                    </select>
                    @error('orderAmazonImageModel') <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">Model không hợp lệ.</p> @enderror
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" x-on:click="$dispatch('close-modal', 'order-amazon-model')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Hủy</button>
                        <button type="button" wire:click="saveOrderAmazonImageModel" wire:loading.attr="disabled" wire:target="saveOrderAmazonImageModel" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="saveOrderAmazonImageModel">Lưu model</span><span wire:loading.flex wire:target="saveOrderAmazonImageModel" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang lưu...</span></button>
                    </div>
                </div>
            </x-modal>
            <x-modal name="order-amazon-prompt" maxWidth="3xl" :centered="true" focusable>
                <div role="dialog" aria-modal="true" aria-labelledby="order-amazon-prompt-title" class="bg-white p-5 text-slate-950 dark:bg-slate-900 dark:text-slate-100">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="order-amazon-prompt-title" class="text-lg font-semibold">Prompt dùng chung</h2>
                        <button type="button" x-on:click="$dispatch('close-modal', 'order-amazon-prompt')" aria-label="Đóng" class="rounded-md px-3 py-2 text-sm">&times;</button>
                    </div>
                    <label for="order-amazon-prompt-input" class="mt-4 block text-sm font-semibold">Nội dung prompt</label>
                    <textarea id="order-amazon-prompt-input" wire:model="orderAmazonPrompt" rows="10" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>
                    @error('orderAmazonPrompt') <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" x-on:click="$dispatch('close-modal', 'order-amazon-prompt')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">Hủy</button>
                        <button type="button" wire:click="saveOrderAmazonPrompt" wire:loading.attr="disabled" wire:target="saveOrderAmazonPrompt" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="saveOrderAmazonPrompt">Lưu prompt</span><span wire:loading.flex wire:target="saveOrderAmazonPrompt" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang lưu...</span></button>
                    </div>
                </div>
            </x-modal>
        @endif
                <p class="mt-2 text-sm text-slate-600">Dán nội dung trang chi tiết đơn hàng Amazon. Kết quả không tự lưu đơn.</p>
                <label for="order-text" class="mt-4 block text-sm font-semibold">Nội dung đơn hàng</label>
                <textarea id="order-text" wire:model="orderText" wire:loading.attr="disabled" wire:target="exportOrderText" @disabled($orderTextLocked) rows="8" maxlength="100000" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Dán nội dung đơn hàng vào đây"></textarea>
                @error('orderText') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                @php
                    $designLinksReady = count($orderTextResults) > 0 && collect($orderTextResults)->keys()->every(fn ($index) => !empty($orderTextSkipDesignLink[$index]) || (!empty($orderTextApproved[$index]) && !empty($orderTextMasterDriveLinks[$index])));
                @endphp
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="parseOrderText" wire:loading.attr="disabled" wire:target="parseOrderText,exportOrderText,generateOrderTextMaster" @disabled($orderTextLocked) class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="parseOrderText">Lấy dữ liệu</span><span wire:loading.flex wire:target="parseOrderText" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang lấy dữ liệu...</span></button>
                    @if ($orderTextLocked)<button type="button" wire:click="clearOrderTextInput" wire:loading.attr="disabled" wire:target="clearOrderTextInput,exportOrderText,generateOrderTextMaster,approveOrderTextMaster" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold"><span wire:loading.remove wire:target="clearOrderTextInput">Thêm đơn</span><span wire:loading.flex wire:target="clearOrderTextInput" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang xử lý...</span></button>@endif
                    @if (count($orderTextResults))<button type="button" wire:click="exportOrderText" wire:loading.attr="disabled" wire:target="exportOrderText,orderTextTrackingFiles,generateOrderTextMaster,approveOrderTextMaster" @disabled(!$designLinksReady) title="{{ $designLinksReady ? 'Export đơn' : 'Cần duyệt ảnh hoặc chọn bỏ qua Design Link' }}" class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportOrderText">Export đơn</span>
                        <span wire:loading wire:target="exportOrderText" class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang export...</span>
                    </button>@endif
                </div>
                @if (count($orderTextResults) && !$designLinksReady)
                    <p class="mt-2 text-sm text-amber-700">Cần duyệt đủ ảnh Template hoặc chọn bỏ qua Design Link trước khi export.</p>
                @endif
                <p wire:loading wire:target="orderTextTrackingFiles,exportOrderText" class="mt-2 text-sm">Đang xử lý file và export...</p>
                @if ($errors->has('orderTextExport')) <p role="alert" class="mt-2 text-sm text-red-700">{{ $errors->first('orderTextExport') }}</p> @endif
                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <label class="text-sm"><input type="checkbox" wire:model.live="orderTextFbaOnly" wire:loading.attr="disabled" wire:target="exportOrderText" class="mr-1"> Chỉ FBA</label>
                </div>
                <x-modal name="order-amazon-clear-data" maxWidth="md" :centered="true" focusable>
                    <div x-data="{ clearing: false }" role="dialog" aria-modal="true" aria-labelledby="order-amazon-clear-data-title" class="bg-white p-5 text-slate-950 dark:bg-slate-900 dark:text-slate-100">
                        <h2 id="order-amazon-clear-data-title" class="text-lg font-semibold">Xóa toàn bộ dữ liệu?</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Ảnh Create Master và Template trên máy chủ cũng sẽ bị xóa. Không thể hoàn tác.</p>
                        @error('orderTextMaster') <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p> @enderror
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" x-on:click="$dispatch('close-modal', 'order-amazon-clear-data')" x-bind:disabled="clearing" class="rounded-lg border border-slate-300 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600">No</button>
                            <button type="button" x-on:click="if (!clearing) { clearing = true; try { await $wire.clearOrderTextRows() } finally { clearing = false } }" x-bind:disabled="clearing" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><span x-show="!clearing">Yes, xóa tất cả</span><span x-show="clearing" x-cloak role="status" class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang xóa...</span></button>
                        </div>
                    </div>
                </x-modal>
                <section class="mt-4 rounded-lg border border-slate-200 p-4" aria-labelledby="order-text-results-title">
                    <div class="flex items-center justify-between gap-3">
                        <h3 id="order-text-results-title" class="text-sm font-bold">Dữ liệu đơn hàng</h3>
                        @if (count($orderTextResults))
                            <button type="button" x-on:click="$dispatch('open-modal', 'order-amazon-clear-data')" wire:loading.attr="disabled" wire:target="clearOrderTextRows,exportOrderText,generateOrderTextMaster,approveOrderTextMaster" class="rounded border border-red-200 px-3 py-1 text-xs font-semibold text-red-700 disabled:opacity-50 dark:border-red-900 dark:text-red-300">Xóa tất cả</button>
                        @endif
                    </div>
                    @if (count($orderTextResults))
                        <p class="mt-3 text-sm text-amber-800">Kiểm tra dữ liệu trước khi sử dụng. Dấu gạch ngang là thông tin chưa tìm thấy.</p>
                        <p class="mt-2 text-xs text-slate-600">File tracking được chia sẻ cho bất kỳ ai có link, chỉ quyền xem. Chấp nhận JPG, PNG, WebP hoặc PDF, tối đa 10 MB.</p>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="w-max min-w-full text-left text-xs leading-5 [&_th:not(:last-child)]:border-r [&_td:not(:last-child)]:border-r [&_th]:border-slate-300 [&_td]:border-slate-300">
                                <caption class="sr-only">Dữ liệu đơn hàng và customizations</caption>
                                <thead class="bg-slate-50 dark:bg-slate-800"><tr>
                                    <th scope="col" class="w-14 px-3 py-2 font-semibold">STT</th>
                                    @foreach (['SKU', 'Order ID', 'Order Product ID', 'Qty', 'Design Link', 'Tracking ID', 'TO_NAME', 'TO_COMPANY', 'TO_PHONE', 'TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE'] as $heading)
                                        <th scope="col" class="min-w-40 px-3 py-2 font-semibold">{{ $heading }}</th>
                                    @endforeach
                                    @foreach ($orderTextCustomizationLabels as $label)
                                        @if (!in_array($label, ['ADD NAME', 'ADD YEAR', 'Choose Pack', 'Surface 1', 'SELECT SIZES', 'Would you prefer your item to come with a gift box?'], true))
                                            <th scope="col" class="min-w-40 max-w-52 px-3 py-2 font-semibold">{{ $label }}</th>
                                        @endif
                                    @endforeach
                                    <th scope="col" class="px-3 py-2 font-semibold">Xóa</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($orderTextResults as $index => $row)
                                        @php $address = $row['address']; @endphp
                                        <tr class="align-top" wire:key="order-text-row-{{ $index }}">
                                            <td class="px-3 py-3 font-semibold text-slate-600 dark:text-slate-300">{{ $index + 1 }}</td>
                                            <td class="break-words px-3 py-3">{{ $row['sku'] ?: '-' }}</td>
                                            <td class="break-words px-3 py-3">{{ $row['id'] ?: '-' }}</td>
                                            <td class="break-words px-3 py-3">{{ $row['catalogProduct']?->order_product_id ?? '-' }}</td>
                                            <td class="break-words px-3 py-3">{{ $row['quantity'] ?: '-' }}</td>
                                            <td class="max-w-64 break-all px-3 py-3">
                                                @if (!empty($orderTextApproved[$index]) && !empty($orderTextMasterDriveLinks[$index]))
                                                    <a href="{{ $orderTextMasterDriveLinks[$index] }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">{{ $orderTextMasterDriveLinks[$index] }}</a>
                                                @else
                                                    Wait
                                                @endif
                                                <label class="mt-2 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                                    <input type="checkbox" wire:model.live="orderTextSkipDesignLink.{{ $index }}" wire:loading.attr="disabled" wire:target="exportOrderText,removeOrderTextRow({{ $index }}),clearOrderTextRows" aria-label="Bỏ qua Design Link cho SKU {{ $row['sku'] }} đơn {{ $row['id'] }}">
                                                    Bỏ qua
                                                </label>
                                            </td>
                                            <td class="px-3 py-3">
                                                <label class="sr-only" for="tracking-file-{{ $index }}">File tracking cho đơn {{ $row['id'] ?: $index + 1 }}</label>
                                                <input id="tracking-file-{{ $index }}" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" wire:model="orderTextTrackingFiles.{{ $index }}" wire:loading.attr="disabled" wire:target="exportOrderText,approveOrderTextMaster({{ $index }})" class="w-48 text-xs">
                                                @if (!empty($orderTextTrackingLinks[$index]))<a href="{{ $orderTextTrackingLinks[$index] }}" target="_blank" rel="noopener noreferrer" class="block break-all text-blue-700 underline">{{ $orderTextTrackingLinks[$index] }}</a>@endif
                                                @if ($errors->has('orderTextTrackingFiles.'.$index))<span role="alert" class="block text-red-700">{{ $errors->first('orderTextTrackingFiles.'.$index) }}</span>@endif
                                            </td>
                                            @foreach ($address as $value)<td class="break-words px-3 py-3">{{ $value ?: '-' }}</td>@endforeach
                                            @foreach ($orderTextCustomizationLabels as $label)
                                                @if (!in_array($label, ['ADD NAME', 'ADD YEAR', 'Choose Pack', 'Surface 1', 'SELECT SIZES', 'Would you prefer your item to come with a gift box?'], true))
                                                    <td class="break-words px-3 py-3">{{ $row['customizationValues'][$label] ?? '-' }}</td>
                                                @endif
                                            @endforeach
                                            <td class="px-3 py-3"><button type="button" wire:click="removeOrderTextRow({{ $index }})" wire:confirm="Xóa đơn nháp và ảnh đã tạo của đơn này? Không thể hoàn tác." wire:loading.attr="disabled" wire:target="removeOrderTextRow({{ $index }}),exportOrderText,generateOrderTextMaster,approveOrderTextMaster" aria-label="Xóa đơn {{ $row['id'] ?: $index + 1 }}" class="rounded px-2 py-1 text-red-700"><span wire:loading.remove wire:target="removeOrderTextRow({{ $index }})">&times;</span><span wire:loading.flex wire:target="removeOrderTextRow({{ $index }})" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang xóa</span></button></td>
                                        </tr>
                                        @if ($row['giftBox'])
                                            <tr class="align-top" wire:key="order-text-gift-box-{{ $index }}">
                                                <td class="px-3 py-3">-</td>
                                                <td class="px-3 py-3">{{ $row['giftBox']['sku'] }}</td>
                                                <td class="px-3 py-3">-</td>
                                                <td class="px-3 py-3">{{ $row['giftBox']['productId'] }}</td>
                                                @foreach (range(1, 11) as $unused)<td class="px-3 py-3">-</td>@endforeach
                                                @foreach ($orderTextCustomizationLabels as $label)
                                                    @if (!in_array($label, ['ADD NAME', 'ADD YEAR', 'Choose Pack', 'Surface 1', 'SELECT SIZES', 'Would you prefer your item to come with a gift box?'], true))<td class="px-3 py-3">-</td>@endif
                                                @endforeach
                                                <td class="px-3 py-3">-</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Dán nội dung và chọn Lấy dữ liệu.</p>
                    @endif
                </section>
    </div>
                @if (count($orderTextResults))
                    <section x-data="{ showOrderTemplate: true }" class="mx-auto mt-6 w-full max-w-[1450px] rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="order-text-images-title">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 id="order-text-images-title" class="text-lg font-semibold">Ảnh đơn hàng</h2>
                            <button type="button" x-on:click="showOrderTemplate = !showOrderTemplate" class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"><span x-text="showOrderTemplate ? 'Ẩn template' : 'Hiện template'"></span></button>
                        </div>
                        @error('orderTextMaster') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-slate-600">Khi duyệt, ảnh Template và file tracking đã chọn được upload lên Drive, cho phép bất kỳ ai có link xem. File tracking chọn sau sẽ upload khi export.</p>
                        @foreach ($orderTextResults as $index => $row)
                            <article class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900" wire:key="order-master-{{ $index }}">
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                    <div class="flex min-w-0 flex-wrap items-center gap-2 text-sm">
                                        <span class="font-semibold">STT: {{ $index + 1 }}</span>
                                        <span aria-hidden="true" class="text-slate-400">|</span>
                                        <h4 class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 font-semibold text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">SKU: {{ $row['sku'] ?: '-' }}</h4>
                                        <span aria-hidden="true" class="text-slate-400">|</span>
                                        <span class="break-all text-slate-600 dark:text-slate-300">Order: {{ $row['id'] ?: '-' }}</span>
                                        @if (trim((string) ($row['customizationValues']['ADD NAME'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD NAME']) !== '-')
                                            <span aria-hidden="true" class="text-slate-400">|</span>
                                            <span class="min-w-0 break-words text-slate-700 dark:text-slate-200">ADD NAME: {{ $row['customizationValues']['ADD NAME'] }}</span>
                                        @endif
                                        @if (trim((string) ($row['customizationValues']['ADD YEAR'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD YEAR']) !== '-')
                                            <span aria-hidden="true" class="text-slate-400">|</span>
                                            <span class="min-w-0 break-words text-slate-700 dark:text-slate-200">ADD YEAR: {{ $row['customizationValues']['ADD YEAR'] }}</span>
                                        @endif
                                    </div>
                                    @if (!empty($orderTextTemplateImages[$index]))
                                        <button type="button" wire:click="approveOrderTextMaster({{ $index }})" wire:loading.attr="disabled" wire:target="approveOrderTextMaster({{ $index }}),generateOrderTextMaster({{ $index }}),exportOrderText" @disabled(!empty($orderTextApproved[$index])) class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove wire:target="approveOrderTextMaster({{ $index }})">{{ !empty($orderTextApproved[$index]) ? 'Đã duyệt' : 'Duyệt ảnh' }}</span><span wire:loading.flex wire:target="approveOrderTextMaster({{ $index }})" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang upload...</span></button>
                                    @endif
                                </div>
                                @if ((trim((string) ($row['customizationValues']['ADD NAME'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD NAME']) !== '-') || (trim((string) ($row['customizationValues']['ADD YEAR'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD YEAR']) !== '-'))
                                    <div class="mt-4 flex flex-wrap gap-4">
                                        @if (trim((string) ($row['customizationValues']['ADD NAME'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD NAME']) !== '-')
                                            <label class="text-sm text-slate-700 dark:text-slate-200">Xử lý tên (chữ gốc cần thay)
                                                <select wire:model.live="orderTextReplacementSources.{{ $index }}.name_mode" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900"><option value="replace">Thay chữ có sẵn</option><option value="add">Thêm chữ mới</option></select>
                                                @if (($orderTextReplacementSources[$index]['name_mode'] ?? 'replace') === 'replace')
                                                <input type="text" aria-label="Chữ tên cần thay trong ảnh" wire:model="orderTextReplacementSources.{{ $index }}.name" placeholder="Chữ đang có: Name" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900">
                                                @endif
                                                @if (($orderTextReplacementSources[$index]['name_mode'] ?? 'replace') === 'add')
                                                <input type="text" aria-label="Vị trí thêm tên trong ảnh" wire:model="orderTextReplacementSources.{{ $index }}.name_position" placeholder="Vị trí thêm chữ" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900">
                                                @endif
                                            </label>
                                        @endif
                                        @if (trim((string) ($row['customizationValues']['ADD YEAR'] ?? '')) !== '' && trim((string) $row['customizationValues']['ADD YEAR']) !== '-')
                                            <label class="text-sm text-slate-700 dark:text-slate-200">Xử lý năm
                                                <select wire:model.live="orderTextReplacementSources.{{ $index }}.year_mode" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900"><option value="replace">Thay chữ có sẵn</option><option value="add">Thêm chữ mới</option></select>
                                                @if (($orderTextReplacementSources[$index]['year_mode'] ?? 'replace') === 'replace')
                                                <input type="text" aria-label="Chữ năm cần thay trong ảnh" wire:model="orderTextReplacementSources.{{ $index }}.year" placeholder="Chữ đang có: Year" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900">
                                                @endif
                                                @if (($orderTextReplacementSources[$index]['year_mode'] ?? 'replace') === 'add')
                                                <input type="text" aria-label="Vị trí thêm năm trong ảnh" wire:model="orderTextReplacementSources.{{ $index }}.year_position" placeholder="Vị trí thêm chữ" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 dark:border-slate-600 dark:bg-slate-900">
                                                @endif
                                            </label>
                                        @endif
                                    </div>
                                @endif
                                <div class="mt-4 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                                    <div class="min-w-0">
                                        <h5 class="mb-2 flex min-h-8 items-center text-xs font-bold uppercase text-slate-600 dark:text-slate-300">1. Source Image</h5>
                                        <div class="flex aspect-[4/4.45] items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                            @if (!empty($orderTextSourcePreviews[$index]))
                                                <button type="button" aria-label="Xem lớn ảnh gốc SKU {{ $row['sku'] }}" x-on:click="$dispatch('review-image', { src: @js($orderTextSourcePreviews[$index]), original: @js($orderTextSourcePreviews[$index]), title: @js('Ảnh gốc SKU '.$row['sku']) })" class="flex h-full w-full cursor-zoom-in items-center justify-center">
                                                    <img src="{{ $orderTextSourcePreviews[$index] }}" alt="Ảnh gốc SKU {{ $row['sku'] }}" class="h-full w-full object-contain" loading="lazy">
                                                </button>
                                            @else<p class="px-4 text-center text-sm text-slate-500 dark:text-slate-400">Không tìm thấy ảnh gốc.</p>@endif
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="mb-2 flex min-h-8 items-center justify-between gap-2">
                                            <h5 class="text-xs font-bold uppercase text-blue-700 dark:text-blue-300">2. Create Master</h5>
                                            <button type="button" wire:click="generateOrderTextMaster({{ $index }})" wire:loading.attr="disabled" wire:target="approveOrderTextMaster({{ $index }}),generateOrderTextMaster({{ $index }}),exportOrderText" @disabled(empty($orderTextSourcePreviews[$index])) class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800 disabled:opacity-50 dark:bg-blue-950 dark:text-blue-200"><span wire:loading.remove wire:target="generateOrderTextMaster({{ $index }})">Create Master</span><span wire:loading.flex wire:target="generateOrderTextMaster({{ $index }})" role="status" class="items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Đang tạo ảnh...</span></button>
                                        </div>
                                        <div class="relative flex aspect-[4/4.45] items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                            <div wire:loading.flex wire:target="generateOrderTextMaster({{ $index }})" class="absolute inset-0 z-10 items-center justify-center bg-slate-50 dark:bg-slate-950" role="status"><x-spinner /><span class="sr-only">Đang tạo ảnh</span></div>
                                            @if (!empty($orderTextMasterPreviews[$index]))
                                                <button type="button" aria-label="Xem lớn ảnh Create Master SKU {{ $row['sku'] }}" x-on:click="$dispatch('review-image', { src: @js($orderTextMasterPreviews[$index]), original: @js($orderTextMasterPreviews[$index]), title: @js('Ảnh Create Master SKU '.$row['sku']) })" class="flex h-full w-full cursor-zoom-in items-center justify-center">
                                                    <img src="{{ $orderTextMasterPreviews[$index] }}" alt="Ảnh AI SKU {{ $row['sku'] }}" class="h-full w-full object-contain" loading="lazy">
                                                </button>
                                            @else<p class="px-4 text-center text-sm text-slate-500 dark:text-slate-400">Chọn Create Master để tạo ảnh.</p>@endif
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        @php
                                            $templateProducts = $row['designs']->pluck('product.name')->filter()->unique()->values();
                                            $productName = $templateProducts->count() === 1 ? $templateProducts->first() : '';
                                            $templateType = preg_match('/\b(glass|ceramic)\b/i', $productName, $templateMatch) ? strtolower($templateMatch[1]) : '';
                                        @endphp
                                        <div class="mb-2 flex min-h-8 items-center justify-between gap-2">
                                            <h5 class="text-xs font-bold uppercase text-slate-600 dark:text-slate-300">3. Template</h5>
                                            <div class="flex items-center gap-1">
                                                <button type="button" wire:loading.attr="disabled" wire:target="approveOrderTextMaster({{ $index }}),generateOrderTextMaster({{ $index }}),exportOrderText" @disabled(empty($orderTextMasterPreviews[$index])) x-on:click="$dispatch('order-template-open', { index: {{ $index }}, type: @js($templateType), image: @js($orderTextMasterPreviews[$index] ?? ''), templates: @js($orderAmazonBoundsTemplates) })" class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800 disabled:opacity-50 dark:bg-blue-950 dark:text-blue-200">Template</button>
                                            </div>
                                        </div>
                                        <div class="relative flex aspect-[4/4.45] items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                            @if (!empty($orderTextTemplatePreviews[$index]))
                                                <button type="button" aria-label="Xem lớn ảnh đã căn template SKU {{ $row['sku'] }}" x-on:click="$dispatch('review-image', { src: @js($orderTextTemplatePreviews[$index]), original: @js($orderTextTemplatePreviews[$index]), title: @js('Ảnh đã căn template SKU '.$row['sku']) })" class="relative z-0 flex h-full w-full cursor-zoom-in items-center justify-center">
                                                    <img src="{{ $orderTextTemplatePreviews[$index] }}" alt="Ảnh đã căn template SKU {{ $row['sku'] }}" class="h-full w-full object-contain">
                                                </button>
                                            @else
                                                <p class="px-4 text-center text-sm text-slate-500 dark:text-slate-400">Chưa lưu ảnh Template.</p>
                                            @endif
                                            @if (!empty($orderAmazonBoundsTemplates[$templateType]))
                                                <img x-show="showOrderTemplate" src="{{ $orderAmazonBoundsTemplates[$templateType] }}" alt="" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-contain opacity-60">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($orderTextMasterErrors[$index]))<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $orderTextMasterErrors[$index] }}</p>@endif
                            </article>
                        @endforeach
                    </section>
                @endif
</section>


