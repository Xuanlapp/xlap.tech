<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Form Details
    </div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">

        <!-- Config and Form -->
        <div class="grid-cols-12 grid p-1 gap-3">
            <div class="col-span-1">
                <x-label class="mt-2" value="Form"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="form" autofocus/>
                @error('form') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-6">
                <x-label class="mt-2" value="Insert Name"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="insert_name" autofocus/>
                @error('insert_name') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-3">
                <x-label class="mt-2" value="Config"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="config" autofocus/>
                {{--                <x-input class="block mt-1 w-full" type="text" wire:model.defer="config" autofocus/>--}}
                @error('config') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-1">
                <x-label class="mt-2" value="Cards"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="cards" autofocus/>
                @error('cards') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-1">
                <x-label class="mt-2" value="Seq"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="seq" autofocus/>
                @error('seq') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Prepress Colors Front and Back -->
        <div class="grid-cols-12 grid p-1 gap-3">
            <div class="col-span-7">
                <x-label class="mt-2" value="Prepress Colors Front"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="prepress_colors_front"
                         autofocus/>
                @error('prepress_colors_front') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-5">
                <x-label class="mt-2" value="Prepress Colors Back"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="prepress_colors_back"
                         autofocus/>
                @error('prepress_colors_back') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

        </div>

        {{-- Coating and Lam --}}
        <div class="grid-cols-12 grid p-1 gap-3">
            <div class="col-span-3">
                <x-label class="mt-2" value="Lam Front"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="lam_front" autofocus/>
                @error('lam_front') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-3">
                <x-label class="mt-2" value="Lam Back"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="lam_back" autofocus/>
                @error('lam_back') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-3">
                <x-label class="mt-2" value="Coating Front"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="coating_front" autofocus/>
                @error('coating_front') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-3">
                <x-label class="mt-2" value="Coating Back"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="coating_back" autofocus/>
                @error('coating_back') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Seq, Substrate, and Foil -->
        <div class="grid-cols-12 grid p-1 gap-3">

            <div class="col-span-4">
                <x-label class="mt-2" value="Substrate"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="substrate" autofocus/>
                @error('substrate') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <x-label class="mt-2" value="Foil"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="foil" autofocus/>
                @error('foil') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-4">
                <x-label class="mt-2" value="Autos"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="autos" autofocus/>
                @error('autos') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <x-label class="mt-2" value="PMS"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="pms" autofocus/>
                @error('pms') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Panini, Leagues, Stamped, None -->
        <div class="grid-cols-12 grid p-1 gap-3">
            <div class="col-span-2">
                <x-label class="mt-2" value="Panini"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="panini" autofocus/>
                @error('panini') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <x-label class="mt-2" value="Leagues"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="leagues" autofocus/>
                @error('leagues') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <x-label class="mt-2" value="Stamped"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="stamped" autofocus/>
                @error('stamped') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-4">
                <x-label class="mt-2" value="Panini Binder"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="panini_binder" autofocus/>
                @error('panini_binder') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <x-label class="mt-2" value="Total Inc Sht"/>
                <x-input class="block mt-1 w-full" type="text" wire.ignore wire:model.defer="total_inc_sht" autofocus/>
                @error('total_inc_sht') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Panini Binder, Total Inc Sht, Program ID, Insert Short Name -->
        <div class="grid-cols-12 grid p-1 gap-3">

            <div class="col-span-3">
                <x-label class="mt-2" value="Insert Short Name"/>
                <x-input class="block mt-1 w-full" type="text" wire:model.defer="insert_short_name" maxlength="10"
                         autofocus/>
                @error('insert_short_name') <span class="error text-red-400">{{ $message }}</span> @enderror
            </div>
        </div>

    </div>

    <!-- Buttons -->
    <div class="px-6 py-4 bg-gray-100 text-right">
        <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">
            Cancel
        </x-secondary-button>
        <x-button class="text-right" wire:click="updateFormDetail" wire:loading.attr="disabled"
                  onclick="confirm('Are you sure you want to change?') || event.stopImmediatePropagation()">
            <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
            Save
        </x-button>
    </div>
</div>
