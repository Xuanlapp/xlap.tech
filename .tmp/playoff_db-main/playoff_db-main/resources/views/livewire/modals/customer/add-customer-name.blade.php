<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Add Customer Name
    </div>
    <div class="px-6 py-4 bg-slate-100">
        <x-label class="mt-2" value="Customer Name"/>
        <x-input class="block mt-1 w-full" type="text" wire:model="customer_name" autofocus/>
        @error('customer_name') <span class="error text-red-400">{{ $message }}</span> @enderror

        <div class="mt-4 text-right">
            <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>
            <x-button class="ml-2 bl" wire:click="save" wire:loading.attr="disabled">
                Save
            </x-button>
        </div>
    </div>
</div>