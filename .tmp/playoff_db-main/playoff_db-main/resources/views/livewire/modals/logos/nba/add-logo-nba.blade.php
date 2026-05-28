<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        {{ __($status === 'edit' ? 'Edit Basketball Team' : 'Add Basketball Team') }}
    </div>
    <div class="px-6 py-4 bg-slate-100">
        <form wire:submit.prevent="  {{ $status == 'edit' ? 'UpdateTeam' : 'AddBKBTeam' }}">
            <x-atomics.input-field label="Full Team Name" model="team_name" type="text" class="col-span-6"/>

            <div class="flex">
                <x-label class="mt-2">
                    Team Kind
                </x-label>
            </div>
            <x-molecules.select-list-modal :data="$team_kind" placeholder="Select Team" wire:model="kind"/>
            <div class="flex">
                <x-label class="mt-2">
                    Parent
                </x-label>
            </div>
            <x-molecules.select-list-modal :data='$parent_ids' placeholder="Select Team"
                                           wire:model="parent_id"/>
            <div class="flex">
                <x-atomics.input-field label="Team Name Only" model="stat_name" type="text" class="col-span-3"/>
                <x-atomics.input-field label="Team Abreviations" model="team_abb" type="text"
                                       class="col-span-2 ml-2"/>
                <x-atomics.input-field label="Team Id" model="team_id" type="number" class="col-span-2 ml-2"/>
            </div>
            <div class="flex">
                <x-atomics.input-field label="Team City Name" model="city" type="text" class="col-span-2"/>
                <x-atomics.input-field label="Pickup Name" model="pickup_name" type="text" class="col-span-2 ml-2"/>
                <x-atomics.input-field label="Init Letters" model="init_letters" type="text" class="col-span-2 ml-2"/>
            </div>

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