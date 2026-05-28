<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Add Location Contact
    </div>
    <div class="px-6 py-4 bg-slate-100">
        <form wire:submit.prevent="save">
            <x-atomics.input-field label="Name" model="name" type="text" class="col-span-4"/>
            <x-atomics.input-field label="Email" model="email" type="email" class="col-span-4"/>
            <x-atomics.input-field label="Phone" model="phone" type="text" class="col-span-4"/>
            <x-atomics.input-field label="Position" model="position" type="text" class="col-span-4"/>
            <x-atomics.input-field label="Profile Image" type="file" model="profile_image" class="col-span-4"/>
            @if($profile_image)
                <img src="{{ $profile_image->temporaryUrl() }}" class="w-20 h-20  mx-auto"
                     alt="Profile Image">
            @endif
            <x-label for="department_id" value="Department" class="col-span-4"/>
            <select wire:model="department_id" id="department_id"
                    class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-400 shadow-sm focus:shadow-md appearance-none cursor-pointer">
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
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

