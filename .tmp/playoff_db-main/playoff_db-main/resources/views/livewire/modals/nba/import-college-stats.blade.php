<div class="px-6 py-4 bg-slate-100 space-y-3">
    <!-- Thông báo thành công -->
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <!-- Thông báo lỗi -->
    @if (session()->has('error'))
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form tải file -->
    <form wire:submit.prevent="import" enctype="multipart/form-data">
        <label for="file" class="block mb-2 text-sm font-medium text-gray-900">Upload Excel File</label>
        <input type="file" wire:model="file_nba_college"
               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
               required>
        @error('file_nba_college')
        <span class="text-red-500">{{ $message }}</span>
        @enderror
        <button type="submit" class="mt-3 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Import
        </button>
    </form>
</div>
