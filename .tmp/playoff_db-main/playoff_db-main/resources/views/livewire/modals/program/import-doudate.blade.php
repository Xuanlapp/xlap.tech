<div class="bg-white p-10 rounded-md shadow-sm">
    <h3 class="text-lg font-medium mb-2">Upload Due Date File</h3>
    <form wire:submit.prevent="uploadDouDateFile" enctype="multipart/form-data">
        <input type="file" wire:model="DouDateFile"
               class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
               required>
        @error('newFile')
        <span class="text-red-500">{{ $message }}</span>
        @enderror
        <button type="submit" class="mt-3 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
            Upload
        </button>
    </form>
    @if (session()->has('newFileSuccess'))
        <div class="mt-3 text-green-600">{{ session('newFileSuccess') }}</div>
    @endif
    @if (session()->has('newFileError'))
        <div class="mt-3 text-red-600">{{ session('newFileError') }}</div>
    @endif
</div>
