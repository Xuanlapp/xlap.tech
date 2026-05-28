<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Add Location Contact
    </div>
    <div class="px-6 py-4 bg-slate-100">
        <form wire:submit.prevent="save">
            <x-atomics.input-field label="Location Name" model="location_name" type="text" class="col-span-4"/>
            <x-atomics.input-field label="Address" model="address" type="text" class="col-span-4"/>
            <div class="text-right px-6 py-4">
                <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">Cancel
                </x-secondary-button>
                <x-button type="submit" wire:loading.attr="dis1abled"
                >
                    <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
                    Save
                </x-button>
            </div>
        </form>
    </div>
</div>