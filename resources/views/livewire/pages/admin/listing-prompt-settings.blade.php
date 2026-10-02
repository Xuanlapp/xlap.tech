<section class="min-h-[calc(100vh-4rem)] bg-[#f4f6fb] px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-5">
        <div><p class="text-sm font-medium text-slate-500">Admin</p><h1 class="mt-2 text-3xl font-semibold text-slate-950">Listing Prompt Settings</h1><p class="mt-2 text-sm text-slate-500">Chinh prompt mac dinh theo tung san pham va marketplace. Prompt duoc luu vao database.</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700">Product<select wire:model.live="productId" class="mt-1 block w-full rounded-lg border-slate-300">@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
                <label class="text-sm font-semibold text-slate-700">Marketplace<select wire:model.live="marketplace" class="mt-1 block w-full rounded-lg border-slate-300"><option value="amazon">Amazon</option><option value="etsy">Etsy</option></select></label>
            </div>
            <label class="mt-5 block text-sm font-semibold text-slate-700">Prompt<textarea wire:model="content" rows="28" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs leading-5"></textarea></label>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-bold text-slate-900">Input mapping</h2>
                    <p class="mt-1 text-xs text-slate-500">Chọn nguồn có sẵn. Không cần nhập tên cột hoặc biến bằng tay.</p>
                    @foreach($inputMapping as $key => $value)
                        <label class="mt-3 block text-xs font-semibold text-slate-700">{{ $key }}
                            <select wire:model="inputMapping.{{ $key }}" class="mt-1 block w-full rounded-md border-slate-300 bg-white text-xs font-normal">
                                @foreach($inputOptions[$key] ?? [] as $optionValue => $optionLabel)<option value="{{ $optionValue }}">{{ $optionLabel }}</option>@endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-sm font-bold text-slate-900">Output mapping</h2>
                    <p class="mt-1 text-xs text-slate-500">Chọn cột an toàn để lưu. Không thể nhập cột tùy ý.</p>
                    @foreach($outputMapping as $key => $value)
                        <label class="mt-3 block text-xs font-semibold text-slate-700">{{ $key }}
                            <select wire:model="outputMapping.{{ $key }}" class="mt-1 block w-full rounded-md border-slate-300 bg-white text-xs font-normal">
                                @foreach($outputOptions as $optionValue => $optionLabel)<option value="{{ $optionValue }}">{{ $optionLabel }}</option>@endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 p-4">
                <h2 class="text-sm font-bold text-blue-950">Preview dữ liệu đầu vào</h2>
                <div class="mt-3 grid gap-2 text-xs text-blue-900 sm:grid-cols-2">@foreach($sampleData as $key => $value)<div><span class="font-bold">{{ $key }}:</span> {{ is_scalar($value) ? ($value ?: 'N/A') : 'N/A' }}</div>@endforeach</div>
            </div>
            <p class="mt-2 text-xs font-medium text-slate-500">Bạn có thể lưu prompt, mapping đầu vào và mapping đầu ra cùng một cấu hình product/marketplace.</p>
            @foreach($errors->all() as $error)<p class="mt-2 text-sm text-red-600">{{ $error }}</p>@endforeach
            <div class="mt-4 flex flex-wrap justify-end gap-3"><button type="button" wire:click="resetDefault" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset mặc định</button><button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu prompt</button></div>
        </div>
    </div>
</section>
