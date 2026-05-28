<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Contact Department Detail
    </div>
    <div class="bg-gray-100 pt-0 p-4 rounded ">
        <form wire:submit.prevent="updateContactDepartment">
            <x-atomics.input-field label="Department Name" model="department_name" type="text" class="col-span-4"/>
            <x-label for="location_id" value="Location" class="mt-2"/>

            <select wire:model="location_id" id="location_id"
                    class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-400 shadow-sm focus:shadow-md appearance-none cursor-pointer">
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                @endforeach
            </select>

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
