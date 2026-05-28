<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Parallel Detail
    </div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <x-program.program-card programId="{{$programId}}"/>
    </div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">

        <div class="flex gap-3 items-center justify-center mt-4">
            <x-atomics.badge :color="'blue'" :text="'Form' "/>
            <x-label class="text-3xl text-blue-600 ml-2" :value="' ' . $form"/>
        </div>
        <!-- Config and Form -->
        <form wire:submit.prevent="updateSubFormDetail">
            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Insert Name" model="insert_name" type="text" class="col-span-4"/>
                <x-atomics.input-field label="Confirm" model="config" type="text" class="col-span-3 bg-slate-100"
                                       disabled/>
                <x-atomics.input-field label="Cards" model="cards" type="text" class="col-span-3 bg-slate-100"
                                       disabled/>
                <x-atomics.input-field label="Seq" model="seq" type="text" class="col-span-2 bg-slate-100" disabled/>
            </div>
            <!-- Prepress Colors Front and Back -->
            <div class="grid-cols-12 grid p-1 gap-3">

                <x-atomics.input-field label="Prepress Colors Front" model="prepress_color_front" type="text"
                                       class="col-span-7"/>
                <x-atomics.input-field label="Prepress Colors Back" model="prepress_color_back" type="text"
                                       class="col-span-5"/>
            </div>

            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Lam Front" model="lam_front" type="text"
                                       class="col-span-3"/>
                <x-atomics.input-field label="Lam Back" model="lam_back" type="text"
                                       class="col-span-3"/>
                <x-atomics.input-field label="Coating Front" model="coating_front" type="text"
                                       class="col-span-3"/>
                <x-atomics.input-field label="Coating Back" model="coating_back" type="text"
                                       class="col-span-3"/>
            </div>

            <!-- Seq, Substrate, and Foil -->
            <div class="grid-cols-12 grid p-1 gap-3">
                <x-atomics.input-field label="Substrate" model="substrate" type="text"
                                       class="col-span-4"/>
                <x-atomics.input-field label="Foil" model="foil" type="text"
                                       class="col-span-2"/>
                <x-atomics.input-field label="Autos" model="autos" type="text"
                                       class="col-span-4"/>
                <x-atomics.input-field label="PMS" model="pms" type="text"
                                       class="col-span-2"/>
            </div>

            <!-- Panini, Leagues, Stamped, None -->
            <div class="grid-cols-12 grid p-1 gap-3">

                <x-atomics.input-field label="Panini" model="panini" type="text"
                                       class="col-span-2"/>
                <x-atomics.input-field label="Leagues" model="leagues" type="text"
                                       class="col-span-2"/>
                <x-atomics.input-field label="Stamped" model="stamped" type="text"
                                       class="col-span-2"/>
                <x-atomics.input-field label="Panini Binder" model="panini_binder" type="text"
                                       class="col-span-4"/>
                <x-atomics.input-field label="Total Inc Sht" model="total_inc_sht" type="text"
                                       class="col-span-2"/>
            </div>
            <div class="text-right px-6 py-4">
                <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">Cancel
                </x-secondary-button>
                <x-button type="submit" wire:loading.attr="disabled"
                        {{--                          onclick="confirm('Are you sure you want to change?') || event.stopImmediatePropagation()"--}}
                >
                    <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
                    Save
                </x-button>
            </div>
        </form>
    </div>


</div>
