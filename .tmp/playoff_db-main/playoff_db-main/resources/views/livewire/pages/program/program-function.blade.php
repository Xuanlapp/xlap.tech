<div>
    <div class=" text-center mt-4  flex items-center justify-center  text-white">
        <x-label class="text-xl">Program Function</x-label>
    </div>
    <div class="bg-white p-4 rounded-md shadow-sm mt-3 flex">
        <div class="m-4">
            <x-label>Send Email</x-label>
            <div class="mt-2">
                <x-button onclick="Livewire.emit('openModal', 'modals.program.send-mail')"
                          class="bg-customGreen hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Send email
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Change to Insert Short Name maximum of 10 characters</x-label>
            <div class="mt-2">
                <x-button wire:click="updateInsertShortName()"
                          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="updateInsertShortName" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Upload Insert Short Name
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Converting Color Front and Back to Front and Back Json</x-label>
            <div class="mt-2">
                <x-button wire:click="processPrepressData()"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="processPrepressData" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Process Prepress Data
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Group All Sub Form</x-label>
            <div class="mt-2">
                <x-button wire:click="GroupSubForm()"
                          class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded"
                          onclick="confirm('Are you sure you want to change the group form?') || event.stopImmediatePropagation()">
                    <x-icons.spinner wire:loading wire:target="GroupSubForm" class="fill-white h-5 mr-1" size="6"/>
                    Group Sub Form
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Export All Front and Back Color</x-label>
            <div class="mt-2">
                <x-button wire:click="ExportAllColor()"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="ExportAllColor" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Export All Color
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Process Color</x-label>
            <div class="mt-2">
                <x-button wire:click="processColors()"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="processColors" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Process Color
                </x-button>
            </div>
        </div>
        <div class="m-4">
            <x-label>Change JSON Color</x-label>
            <div class="mt-2">
                <x-button
                        onclick="window.open('{{ route('program.unsure.color') }}') " target="_blank"
                        class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="changeJsonColor" class="fill-white h-5 mr-1" size="6"/>
                    Change JSON Color
                </x-button>
            </div>
        </div>

    </div>
    <div class=" text-center mt-4  flex items-center justify-center  text-white">
        <x-label class="text-xl">Insert Name Color Function</x-label>
    </div>
    <div class="bg-white p-4 rounded-md shadow-sm mt-3 flex">
        <div class="m-4">
            <x-label>Remove parentheses in insert_name</x-label>
            <div class="mt-2">
                <x-button wire:click="parenthesesISN()"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="parenthesesISN" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Remove parentheses
                </x-button>
            </div>
        </div>

        <div class="m-4">
            <x-label>Copy subform to insert_name_color</x-label>
            <div class="mt-2">
                <x-button wire:click="GiveVersionColor()"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="GiveVersionColor" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Give version
                </x-button>
            </div>
        </div>

        <div>
            <!-- Trường nhập CMYK -->
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                   type="text" wire:model="cmyk" placeholder="Type player name here">

            <!-- Hiển thị Hex khi có giá trị -->
            @if($hexColor)
                <x-label class="text-green-500">Hex Color: <span style="color: {{ $hexColor }}">{{ $hexColor }}</span>
                </x-label>
                <!-- Hiển thị trực tiếp màu -->
                <div style="background-color: {{ $hexColor }}; padding: 10px; color: white; margin-top: 10px;">
                    This is the color preview!
                </div>
            @endif
        </div>

    </div>
    <div class=" text-center mt-4  flex items-center justify-center  text-white">
        <x-label class="text-xl">NBA Function</x-label>
    </div>
    <div class="bg-white p-4 rounded-md shadow-sm mt-3 flex">

        <div class="m-4">
            <x-label>Change full team name of acronym</x-label>
            <div class="mt-2">
                <x-atomics.button-info wire:click="Nameteamfull()"
                                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <x-icons.spinner wire:loading wire:target="Nameteamfull" class="fill-white h-5 mr-1"
                                     size="6"/>
                    Team Full Name
                </x-atomics.button-info>
            </div>
        </div>
    </div>
</div>
