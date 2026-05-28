<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-10">
        {{-- First column filter start --}}
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                <span>Filter by Team</span>
            </div>
            <div class="flex">
                @foreach($team_kind as $key => $value)
                    <div class="flex items-center mr-5">
                        <input type="radio" value="{{$key}}" wire:model="kind" name="list-radio" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="list-radio-license" class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">{{$key}}</label>
                    </div>
                @endforeach
            </div>
            <x-molecules.select-list wire:model="selected_team" placeholder="Select Team">
                <option>Select Team</option>
                @foreach($team_kind["{$kind}"] as $item)
                    <option value="{{$item->team_name}}">
                        {{$item->team_name.' - '. $item->kind}}
                    </option>
                @endforeach
            </x-molecules.select-list>
        </div>
        {{-- First column filter end --}}
        {{-- Second column filter start --}}
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span>Search by Name</span>
            </div>
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
        {{-- Second column filter end --}}
    </div>

    @role('admin')
    <div class="bg-white shadow-sm rounded-md p-3 space-x-3 flex">
        <div class="space-y-3">
            <div class="flex space-x-2">
                <button
                    class="bg-red-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-700 focus:outline-none focus:border-red-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"
                    wire:click='$emit("openModal", "modals.nba.find-nba-player-id")'>
                    <svg class="w-6 h-6" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor"
                         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"></path>
                    </svg>
                    <span> Find NBA Player Id</span></button>
            </div>

        </div>
        <div>
            <!-- Thông báo -->
            @if (session()->has('success'))
                <div class="mt-3 text-green-600">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="mt-3 text-red-600">{{ session('error') }}</div>
            @endif
        </div>
    </div>
    @endrole


    <div class="mt-3 shadow-sm rounded-md p-3 space-x-3 bg-white">
        <div class="my-5">
            {{ $players->withQueryString()->links() }}
        </div>
        <div class="bg-white shadow-sm rounded-md space-y-5 mb-2 overflow-hidden">
            <table class="table-auto w-full text-left text-gray-500">
                <thead class="bg-slate-200">
                <tr class="h-16">
                    <th class="pl-3">Player</th>

                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach  ($players as $player)

                    <tr class='h-12 even:bg-slate-50 hover:bg-slate-100 flex justify-between items-center'>
                        <td class="pl-3">{{$player->player}}</td>
                        <td>
                            @role('admin|auditor')
                            <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.nba.change-player-id", {{json_encode(['new'=> true, 'player_id' => $player->id])}})'>Assign NBA ID</button>
                            @endrole
                        </td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
            <div class="my-5">
                {{ $players->withQueryString()->links() }}
            </div>
    </div>
</div>
