<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">Email Report</div>
    <div class="px-6 py-4">
        <label for="list-radio-millitary" class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Job</label>
        <input wire:model="job" class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 w-full" type="text" placeholder="Describe Job">
        @error('job') <div class="mt-1 p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">{{ $message }}</div> @enderror
        <label for="list-radio-millitary" class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Comment</label>
        <textarea wire:model="comment" class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" type="text" rows="4" placeholder="Describe the issue about this job"></textarea>
        @error('comment') <div class="mt-1 p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">{{ $message }}</div> @enderror
        <div class='flex justify-end mt-5'>
            <x-atomics.button-green wire:click="submit">Generate Email</x-atomics.button-green>
        </div>
    </div>
</div>
