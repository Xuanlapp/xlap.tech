<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 mt-3 items-center h-24">
        <x-atomics.button-info onclick="window.open('{{ route('program.sure.color') }}') " target="_blank"
                               class="btn mr-4 mt-3 btn-primary">
            Sure Color
        </x-atomics.button-info>
    </div>
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 mt-3 items-center h-24">
        <div class="flex items-center space-x-4">
            <label class="flex items-center text-slate-400">
                <input type="radio" name="sortOption" value="front" wire:model="sortOption" class="mr-2">
                Front
            </label>
            <label class="flex items-center text-slate-400">
                <input type="radio" name="sortOption" value="back" wire:model="sortOption" class="mr-2">
                Back
            </label>
        </div>
    </div>

    <div class="p-6 bg-white rounded-lg shadow-md mb-5">
        <x-label class="text-center">Change JSON Color</x-label>
        @if($sortOption == 'front')
            <x-label class="text-center mt-3">Unsure Color Front</x-label>

        @else
            <x-label class="text-center mt-3">Unsure Color Back</x-label>

        @endif


        <div class="mt-5 space-y-4">
            <table class=" table-auto w-full text-left ">
                <thead class="bg-slate-200">
                <tr>
                    <th class=" px-4 py-2">Name</th>
                    <th class="px-4 py-2">Sure</th>
                    <th class="px-4 py-2">Change Color To</th>
                    <th class="px-4 py-2">To DB</th>
                    <th class="px-4 py-2"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($color as $colorItem)
                    @if($sortOption == 'front' && ($colorItem->color == 1 || $colorItem->color == 3))
                        <tr>
                            <td class="px-4 ">{{ $colorItem->color_name }}</td>
                            <td class="px-4 ">{{ $colorItem->sure }}</td>
                            <td class="px-4">
                                @if($colorItem->sure == 1)
                                    <x-input type="text" value="{{ $colorItem->change_color_to }}"
                                             class="col-span-3 bg-white " disabled/>
                                @else
                                    <x-input type="text" wire:model="colorItems.{{ $colorItem->id }}.change_color_to"
                                             class="col-span-3 bg-slate-100"/>
                                @endif

                            </td>

                            <td class="px-4">
                                @if($colorItem->sure == 1)
                                    <div></div>
                                @else
                                    <x-atomics.button-info
                                            wire:click="change({{ $colorItem->id }})"
                                            class="btn">
                                        Change
                                    </x-atomics.button-info>
                                @endif

                            </td>
                        </tr>
                    @elseif($sortOption == 'back' && ($colorItem->color == 2 || $colorItem->color == 3))
                        <tr>
                            <td class="px-4 ">{{ $colorItem->color_name }}</td>
                            <td class="px-4 ">{{ $colorItem->sure }}</td>
                            <td class="px-4 ">
                                @if($colorItem->sure == 1)
                                    <x-label>{{$colorItem->change_color_to}}</x-label>
                                @else
                                    <x-atomics.input
                                            model="colorItems.{{ $colorItem->id }}.change_color_to"
                                            type="text"
                                            class="col-span-4"/>

                                @endif
                            </td>
                            <td class="px-4">
                                @if($colorItem->sure == 1)
                                    <div></div>

                                @else
                                    <x-atomics.button-info
                                            wire:click="change({{ $colorItem->id }})"
                                            class="btn">
                                        Change
                                    </x-atomics.button-info>
                                @endif

                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
    window.addEventListener('open-new-tab', event => {

        window.open('{{ route('program.sure.color','_blank') }}')
    })
    window.addEventListener('refresh-page', event => {
        window.location.reload(false);
    })
</script>
