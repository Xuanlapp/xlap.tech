<div>
    <x-layouts.modal-layout>
        <!-- Header -->
        <x-slot name="header">
            Program Details {{$collection}} {{$code}}
        </x-slot>

        <!-- Body -->
        <x-slot name="body">
            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Code" model="code" type="text" class="col-span-2" />
                <x-atomics.input-field label="Collection" model="collection" type="text" class="col-span-6" />
                <x-atomics.input-field label="Sport" model="sport" type="text" class="col-span-2" />
                <x-atomics.input-field label="Year" model="year" type="text" class="col-span-2" />
            </div>

            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Program Date/Name" model="date_name" type="text" class="col-span-12" />
            </div>

            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Legal Line" model="legal_line" type="text" class="col-span-12" />
            </div>

            <div class="grid grid-cols-12 gap-3">
                <!-- Auto Build Workflow -->
                <x-atomics.radio-group-field class="col-span-3"
                    label="Auto Build Workflow" 
                    name="auto_build_workflow" 
                    model="auto_build_workflow" 
                    :options="[
                        ['value' => 1, 'label' => 'Y'],
                        ['value' => 0, 'label' => 'N']
                    ]"
                />
                <!-- Outsourced Job -->
                <x-atomics.radio-group-field class="col-span-3"
                    label="Outsourced Job" 
                    name="outsourced_job" 
                    model="outsourced_job" 
                    :options="[
                        ['value' => 1, 'label' => 'Y'],
                        ['value' => 0, 'label' => 'N']
                    ]"
                />
                
                <!-- BK PA Legal -->
                <x-atomics.radio-group-field class="col-span-3"
                    label="BK PA Legal" 
                    name="bk_pa_legal" 
                    model="bk_pa_legal" 
                    :options="[
                        ['value' => 1, 'label' => 'Y'],
                        ['value' => 0, 'label' => 'N']
                    ]"
                />
                
                <!-- Licensed BB Product -->
                <x-atomics.radio-group-field class="col-span-3"
                    label="Licensed BB Product" 
                    name="licensed_bb_product" 
                    model="licensed_bb_product" 
                    :options="[
                        ['value' => 1, 'label' => 'Y'],
                        ['value' => 0, 'label' => 'N']
                    ]"
                />
            </div>
        </x-slot>

        <!-- Footer -->
        <x-slot name="footer">
            <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>
            <x-button class="text-right" wire:click="updateProgramDetail" wire:loading.attr="disabled">
                <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6" />
                Save
            </x-button>
        </x-slot>
    </x-layouts.modal-layout>
</div>

