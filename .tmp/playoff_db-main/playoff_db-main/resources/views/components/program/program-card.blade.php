<div class="bg-white rounded-lg shadow-md mb-3 grid grid-cols-6 overflow-hidden">
    <div class="col-span-1 flex items-center justify-center bg-[{{ $programs->getSportHexColor($programs->sp) }}]">
        {{--        style="background-color: {{ $programs->getSportHexColor() }};">--}}
        <x-dynamic-component :component="'icons.' . $programs->sp" class="fill-white h-20 mr-1"/>
    </div>
    <div class="col-span-5 p-3 flex gap-3 divide-x">
        <div class="px-3 flex items-center">
            <img src="{{ asset($programs->programImage()) }}" alt="Card Front"
                 class="w-36 rounded-md mr-4"
                 onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
        </div>
        <div class="w-full p-3 flex flex-col gap-2">
            <div class='flex flex-wrap'>
                <x-atomics.desc-field class="flex-1" item-class="font-bold text-blue-500">
                    <x-slot name="description">
                        Collection
                    </x-slot>
                    <x-slot name="item">
                        {{ $programs->collection }}
                    </x-slot>
                </x-atomics.desc-field>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Code
                    </x-slot>
                    <x-slot name="item">
                        {{ $programs->code }}
                    </x-slot>
                </x-atomics.desc-field>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Year
                    </x-slot>
                    <x-slot name="item">
                        {{ $programs->year }}
                    </x-slot>
                </x-atomics.desc-field>
                
            </div>
            <div class='flex flex-wrap'>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Total Forms
                    </x-slot>
                    <x-slot name="item">
                        {{ $totalForms }}
                    </x-slot>
                </x-atomics.desc-field>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Total Inserts
                    </x-slot>
                    <x-slot name="item">
                        {{ $totalInserts }}
                    </x-slot>
                </x-atomics.desc-field>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Sport
                    </x-slot>
                    <x-slot name="item">
                        {{ $programs->sp }}
                    </x-slot>
                </x-atomics.desc-field>
                <x-atomics.desc-field class="flex-1">
                    <x-slot name="description">
                        Ship Date
                    </x-slot>
                    <x-slot name="item">
                        {{ $programs->ship }}
                    </x-slot>
                </x-atomics.desc-field>
            </div>
        </div>
    </div>
</div>

