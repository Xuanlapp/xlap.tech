<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">
        Parallel Insert Color Edit
    </div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <x-program.program-card programId="{{$ProgramId}}"/>
    </div>
    <div class="bg-gray-100 pt-0 p-4 rounded ">
        <div>
            <div class="flex justify-between items-start">
                <div class="mt-5">
                    @if($sortOption === 'form' && $SubForm_Id == $firstSubFormId)
                        <div class="w-16"></div>
                    @elseif($isChangedFront || $isChangedBack)
                        <x-secondary-button
                                onclick="if (confirm('Do you want exit without SAVING?')) {  Livewire.emit('previous') }"
                                wire:loading.attr="disabled"
                        >Previous
                        </x-secondary-button>
                    @else
                        <x-button wire:click="previous">Previous</x-button>
                    @endif

                </div>

                <div class="items-center">
                    <x-program.program-sub-form-nav :form="$form"/>
                    <x-label class="text-gray-400 text-3xl">{{$SubForms->insert_name}}</x-label>
                    <x-label class="text-gray-400 text-3xl">{{$SubForms->id}}</x-label>
                </div>
                <div class="mt-5"> @if($isChangedFront || $isChangedBack)
                        <x-secondary-button type="button"
                                            onclick="if (confirm('Do you want exit without SAVING?')) {  Livewire.emit('next') }"
                                            wire:loading.attr="disabled"
                        >Next
                        </x-secondary-button>
                    @else
                        <x-button wire:click="next">Next</x-button>
                    @endif

                </div>
            </div>
        </div>
        <div class="flex justify-between items-center py-2">
            <div class="flex justify-start gap-x-4">
                <!-- Nút Copy -->
                <x-atomics.button-info wire:click="copyColor"
                                       class=" bg-blue-500 hover:bg-blue-700 text-white bg- font-bold py-2 px-4 rounded">
                    Copy Color
                </x-atomics.button-info>


                <!-- Nút Paste -->
                <x-button wire:click="pasteColor" wire.target="pasteColor"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Paste Color
                </x-button>
            </div>
        </div>
        {{--        <div>Lập {{$programssss}}</div>--}}
        {{--        <div>Lập 2 {{$SubForms->prepress_color_front_json}}</div>--}}
        <div>
            <div class="flex justify-between">
                <x-label class="text-black font-bold w-1/4">Version Color:</x-label>
                <x-label class="text-black font-bold w-1/4">Front Color:</x-label>
                <x-label class="text-black font-bold w-1/4">Back Color:</x-label>
            </div>

            @foreach($checkInsertName as $value)
                <div class="flex justify-between items-center mt-2">
                    <div class="w-1/4">
                        <x-secondary-button class="py-2  rounded-md" wire:click="changeVersion({{ $value->id }})">
                            {{ $value->year }}
                        </x-secondary-button>
                    </div>
                    <x-label class="flex bg-gray-50 w-1/4 space-x-2">
                        @foreach($value->prepress_color_front as $color)
                            <span>{{ $color }}</span>
                        @endforeach
                    </x-label>
                    <x-label class="flex bg-gray-50 w-1/4 space-x-2">
                        @foreach($value->prepress_color_back as $color)
                            <span>{{ $color }}</span>
                        @endforeach
                    </x-label>

                   
                </div>

            @endforeach

        </div>

        <div>
            <form wire:submit.prevent="SaveColor">
                <div class=" py-2">
                    <div class="text-right">
                        <x-button type="submit" wire:loading.attr="disabled"
                        >
                            <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
                            Save
                        </x-button>
                        @if($isChangedFront || $isChangedBack)
                            <x-secondary-button
                                    onclick="if (confirm('Do you want exit without SAVING?')) { Livewire.emit('closeModal') }"
                                    wire:loading.attr="disabled">Close
                            </x-secondary-button>
                        @else
                            <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">Close
                            </x-secondary-button>
                        @endif

                    </div>
                </div>


                <div class="flex mt-4">
                    <x-label class="text-black">Prepress Color Front</x-label>
                    @if($isChangedFront)
                        <x-label class="ml-1 text-red-600 ">*</x-label>
                    @endif
                </div>
                <div class="bg-gray-200 p-2 rounded text-sm flex">
                    @foreach($splitdatasFront as $index => $value)
                        {{--                <x-label class="text-gray-700" wire:modal="header">{{ $value }}{{ $loop->last ? '' : ',' }}</x-label>--}}
                        <x-label class="text-gray-700 ml-1"
                                 wire:modal="header">{{ $value }}{{ $loop->last ? '' : ',' }}</x-label>
                    @endforeach
                </div>
                <div class="flex flex-col overflow-auto max-h-96 p-3 bg-white" style="max-height: 500px;">
                    <div class="flex gap-x-6 mb-3">
                        <!-- Phần hiển thị checkbox có qty = 0 -->
                        @foreach($headersFront as $group => $items)
                            <!-- Lọc các item có qty = 0 -->
                            @php
                                $filteredItemsZero = array_filter($items, function($item) {
                                    return $item['qty'] == 0;
                                });
                            @endphp
                            {{-- Single option --}}
                            <!-- Kiểm tra nếu có item nào sau khi lọc -->
                            @if(count($filteredItemsZero) > 0)
                                <div class=" flex flex-col items-center ">
                                    <!-- Group Name -->
                                    <div class="text-center font-bold text-xs mb-2">{{ $group }}</div>

                                    <!-- Checkbox Container - Lọc ra các checkbox trên 1 hàng ngang -->
                                    <div class="flex items-start flex-wrap gap-x-3">
                                        @foreach($filteredItemsZero as $item)
                                            <div class="flex items-center flex-col">
                                                <!-- Checkbox -->

                                                <input type="checkbox"
                                                       class="form-checkbox h-4 w-4 text-blue-600"
                                                       {{ $item['status'] ? 'checked' : '' }}
                                                       wire:change="toggleCheckbox('{{ $item['name'] }}', '{{ $item['checkbox'] }}','{{ $item['numInFront'] }}', $event.target.checked,'front')">
                                                @if($item['checkbox'] == 0)

                                                @else
                                                    <span class="px-2 py-1 rounded text-gray-700">{{ $item['checkbox'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    {{-- Multipli Options --}}
                    <div class="flex flex-col gap-y-3">
                        <!-- Phần hiển thị checkbox có qty != 0 -->
                        @foreach($headersFront as $group => $items)
                            <!-- Lọc các item có qty != 0 -->
                            @php
                                $filteredItemsNonZero = array_filter($items, function($item) {
                                    return $item['qty'] != 0;
                                });
                            @endphp
                            @if(count($filteredItemsNonZero) > 0)
                                <div class="flex items-start mt-3">
                                    <!-- Group Name -->
                                    <div class="text-left font-bold text-xs flex-none w-20">{{ $group }}</div>
                                    <!-- Checkbox Container - Lọc ra các checkbox trên 1 hàng ngang -->
                                    <div class="flex gap-y-1 gap-x-3 items-center flex-wrap ">
                                        @foreach($filteredItemsNonZero as $item)
                                            @if($item['checkbox']>=$item['start'])
                                                <div class="flex flex-col items-center w-8">

                                                    <input type="checkbox"
                                                           class="form-checkbox h-4 w-4 text-blue-600"
                                                           {{ $item['status'] ? 'checked' : '' }}
                                                           wire:change="toggleCheckbox('{{ $item['name'] }}', '{{ $item['checkbox'] }}','{{ $item['numInFront'] }}', $event.target.checked,'front')"
                                                           wire:key="{{$loop->index}}-front"
                                                    >
                                                    <!-- Checkbox -->

                                                    @if($item['checkbox'] == 0)

                                                    @else
                                                        <span class=" px-2 py-1 rounded
                                                           text-gray-500">{{ $item['checkbox'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                {{--                @dump($ProgramId);--}}
                <div class="flex">
                    <x-label class="text-black  mt-6">Prepress Color Back</x-label>
                    @if($isChangedBack)
                        <x-label class="mt-6 ml-1 text-red-600">*</x-label>
                    @endif
                </div>
                {{--                <div>--}}
                {{--                    <pre>--}}
                {{--    {{print_r($headersBack)}}--}}
                {{--    </pre>--}}
                {{--                </div>--}}
                <div class="bg-gray-200 p-2 rounded text-sm flex">
                    @foreach($splitdatasBack as $value)
                        <x-label class="text-gray-700">{{ $value }}
                            {{ $loop->last ? '' : ',' }}</x-label>
                    @endforeach
                </div>
                <div class="flex flex-col overflow-auto max-h-96 p-3 bg-white" style="max-height: 300px;">
                    <div class="flex mt-3 gap-x-8">
                        <!-- Phần hiển thị checkbox có qty = 0 -->
                        @foreach($headersBack as $group => $items)
                            <!-- Lọc các item có qty = 0 -->
                            @php
                                $filteredItemsZero = array_filter($items, function($item) {
                                    return $item['qty'] == 0;
                                });
                            @endphp

                                    <!-- Kiểm tra nếu có item nào sau khi lọc -->
                            @if(count($filteredItemsZero) > 0)
                                <div class="flex flex-col mt-3 items-center gap-y-2">
                                    <!-- Group Name -->
                                    <div class="text-left font-bold text-xs">{{ $group }}</div>

                                    <!-- Checkbox Container - Lọc ra các checkbox trên 1 hàng ngang -->
                                    <div class="flex items-start flex-wrap gap-x-3">
                                        @foreach($filteredItemsZero as $item )
                                            <div class="flex items-center flex-col">

                                                <!-- Checkbox -->
                                                <input type="checkbox"
                                                       class="form-checkbox h-4 w-4 text-blue-600"
                                                       {{ $item['status'] ? 'checked' : '' }}
                                                       wire:change="toggleCheckbox('{{ $item['name'] }}', '{{ $item['checkbox'] }}','{{ $item['numInFront'] }}', $event.target.checked,'back')">
                                                @if($item['checkbox'] == 0)

                                                @else
                                                    <span class="px-2 py-1 rounded text-gray-400">{{ $item['checkbox'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex flex-col gap-y-3">
                        <!-- Phần hiển thị checkbox có qty != 0 -->
                        @foreach($headersBack as $group => $items)
                            <!-- Lọc các item có qty != 0 -->
                            @php
                                $filteredItemsNonZero = array_filter($items, function($item) {
                                    return $item['qty'] != 0;
                                });
                            @endphp
                            @if(count($filteredItemsNonZero) > 0)
                                <div class="flex items-start mt-3">
                                    <!-- Group Name -->
                                    <div class="text-left font-bold text-xs w-12 flex-none"
                                         style="width: 70px">{{ $group }}</div>
                                    <!-- Checkbox Container - Lọc ra các checkbox trên 1 hàng ngang -->
                                    <div class="flex gap-y-1 gap-x-3 items-center flex-wrap ">
                                        @foreach($filteredItemsNonZero as $item)
                                            @if($item['checkbox']>=$item['start'])
                                                <div class="flex flex-col items-center w-8">
                                                    <!-- Checkbox -->
                                                    <input type="checkbox"
                                                           class="form-checkbox h-4 w-4 text-blue-600"
                                                           {{ $item['status'] ? 'checked' : '' }}
                                                           wire:change="toggleCheckbox('{{ $item['name'] }}', '{{ $item['checkbox'] }}','{{ $item['numInFront'] }}', $event.target.checked,'back')">
                                                    @if($item['checkbox'] == 0)

                                                    @else
                                                        <span class="px-2 py-1 rounded text-gray-400">{{ $item['checkbox'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
