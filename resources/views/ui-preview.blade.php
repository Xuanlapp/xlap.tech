<x-guest-layout>
    <div class="min-h-screen bg-slate-50/70 dark:bg-slate-950">
        <div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <header>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">Tailwind UI Preview</h1>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Component Blade dùng chung cho XLAP.</p>
            </header>
        <x-ui.card title="Form mẫu" description="Các control giữ nguyên Blade, Livewire và Tailwind hiện tại.">
            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.form-field label="Tên workflow" for="preview-name" hint="Tên ngắn, dễ nhận biết." required>
                    <x-ui.input id="preview-name" name="preview-name" placeholder="Ví dụ: Etsy Listing" />
                </x-ui.form-field>

                <x-ui.form-field label="Trạng thái" for="preview-status">
                    <x-ui.select id="preview-status" name="preview-status">
                        <option>Đang hoạt động</option>
                        <option>Tạm dừng</option>
                    </x-ui.select>
                </x-ui.form-field>

                <x-ui.form-field class="md:col-span-2" label="Ghi chú" for="preview-note" hint="Có thể dùng trực tiếp với wire:model.">
                    <x-ui.textarea id="preview-note" name="preview-note" placeholder="Nhập ghi chú..."></x-ui.textarea>
                </x-ui.form-field>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-ui.button type="button">Lưu thay đổi</x-ui.button>
                <x-ui.button type="button" variant="outline" color="light">Hủy</x-ui.button>
                <x-ui.badge variant="success">Hoạt động</x-ui.badge>
                <x-ui.badge variant="warning">Cần kiểm tra</x-ui.badge>
                <x-ui.badge variant="danger">Thất bại</x-ui.badge>
            </div>
        </x-ui.card>

        <div class="grid gap-6 md:grid-cols-2">
            <x-ui.card title="Trạng thái lỗi" description="Form field và input cùng biểu thị lỗi, không chỉ dựa vào màu.">
                <x-ui.form-field label="API key" for="preview-key" error="API key không hợp lệ.">
                    <x-ui.input id="preview-key" value="••••••••" invalid />
                </x-ui.form-field>
            </x-ui.card>

            <x-ui.card title="Trạng thái disabled" description="Control bị khóa vẫn đọc được nhưng không thể chỉnh sửa.">
                <x-ui.form-field label="Project" for="preview-project" hint="Project được chọn từ hệ thống.">
                    <x-ui.input id="preview-project" value="XLAP Web Platform" disabled />
                </x-ui.form-field>
            </x-ui.card>
        </div>
        </div>
    </div>
</x-guest-layout>
