<div class="bg-white p-4 rounded-md shadow-sm">
    <h3 class="text-lg font-medium mb-2">Upload Images Folder</h3>
    <form wire:submit.prevent="uploadImage" enctype="multipart/form-data">
        <input type="file" wire:model="selectedFolder"
               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
               webkitdirectory multiple id="folderInput">
        @error('selectedFolder')
        <span class="text-red-500">{{ $message }}</span>
        @enderror
        <button type="submit" class="mt-3 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
            📁 Choose Folder
        </button>
    </form>
    @if (session()->has('success'))
        <div class="mt-3 text-green-600">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mt-3 text-red-600">{{ session('error') }}</div>
    @endif
</div>

