<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        {{ __($status === 'edit' ? 'Edit Basketball Team Version' : 'Add Basketball Team Version') }}
    </div>
    <div class="px-6 py-4 bg-slate-100">
        <form wire:submit.prevent="{{ $status == 'edit' ? 'UpdateVersion' : 'AddVersion' }}">
            <div class="flex">
                <x-atomics.input-field label="Beginning Yr" model="begin" type="number" min="1900"
                                       max="4000" class="col-span-6"/>
                <x-atomics.input-field label="Ending Yr" model="end" type="number" min="1900"
                                       max="4000" class="col-span-6 ml-2"/>
            </div>
            <div class="flex">
                <x-atomics.input-field label="On White" model="on_white" type="text" class="col-span-6"/>
                <x-atomics.input-field label="On Black" model="on_black" type="text" class="col-span-6 ml-2"/>
                <x-atomics.input-field label="On Primary" model="on_primary" type="text" class="col-span-6 ml-2"/>
                <x-atomics.input-field label="On Secondary" model="on_secondary" type="text" class="col-span-6 ml-2"/>

            </div>
            <div class="flex">
                <x-atomics.input-field label="CMYK Pri" model="pri_tc" type="text" class="col-span-6"/>
                <x-atomics.input-field label="CMYK Sec" model="sec_tc" type="text" class="col-span-6 ml-2"/>
            </div>
            @if($logo_version_json)
                <div class="mt-2 flex">
                    <x-label>
                        Logo Version
                    </x-label>
                    <button type="button" wire:click.prevent="addLogoVersionField"
                            class="flex items-center justify-center ml-3 mt-1 text-gray-600 hover:text-blue-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_iconCarrier">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle>
                                <path d="M15 12L12 12M12 12L9 12M12 12L12 9M12 12L12 15" stroke="currentColor"
                                      stroke-width="1.5" stroke-linecap="round"></path>
                            </g>
                        </svg>
                    </button>
                </div>


                <div class="grid grid-cols-5 gap-4 mt-2">
                    @foreach($logo_version_json as $key => $value)
                        <div class="flex flex-col">
                            <div class="col-span-1 relative">
                                <button type="button" wire:click.prevent="deleteLogoVersionField('{{ $key }}')"
                                        class=" absolute -top right-0 flex items-center justify-center text-gray-600 hover:text-red-600 transition-colors duration-200 z-10 bg-white rounded-full">
                                    <svg width="16" height="16" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"
                                         fill="currentColor">
                                        <g id="SVGRepo_iconCarrier">
                                            <g fill="none" fill-rule="evenodd">
                                                <path d="m0 0h32v32h-32z"></path>
                                                <path d="m16 0c8.836556 0 16 7.163444 16 16s-7.163444 16-16 16-16-7.163444-16-16 7.163444-16 16-16zm0 2c-7.7319865 0-14 6.2680135-14 14s6.2680135 14 14 14 14-6.2680135 14-14-6.2680135-14-14-14zm6 13v2h-12v-2z"
                                                      fill="currentColor" fill-rule="nonzero"></path>
                                            </g>
                                        </g>
                                    </svg>
                                </button>
                                <x-atomics.input label=" "
                                                 model="logo_version_json.{{ $key }}"
                                                 type="text"
                                                 class="w-full" wire:model.live="logo_version_json.{{ $key }}"
                                />
                            </div>
                            <div class="mt-2">
                                <x-atomics.team-logo-version :logo='$nba_team' :version='$value'
                                                             title="Preview">
                                    <div class="flex p-0 w-[140px] h-[140px]">
                                        <div class="bg-gray-100 w-[75px] h-[140px]"></div>
                                        <div class="bg-gray-700 w-[75px] h-[140px]"></div>
                                    </div>

                                </x-atomics.team-logo-version>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

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