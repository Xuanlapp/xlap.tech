<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 space-y-2">
    <div class="font-bold mb-3 px-3">Filter</div>
    <div class="flex bg-white shadow-sm rounded-md p-3">
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span>Search by Team</span>
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
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span>Search by Name</span>
            </div>
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
    </div>
    {{-- <div class="bg-white shadow-sm rounded-md p-3 flex space-x-3 items-center">
        <div class="text-6xl">
            🪄
        </div>
        <div class="flex-col space-y-2">
            <div>
                <input type="checkbox" wire:model="selectAll" class="w-6 h-6 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" /> 
                <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Check all</label>
            </div>
            <div>
                <x-atomics.button-info wire:click="approveChecked()"><span class='text-xl'>👍🏻</span> Approve checked</x-atomics.button-info>
            </div>
        </div>
    </div> --}}
    <div class="mb-5">
        {{ $players->withQueryString()->links() }}
    </div>

    {{-- Showing none detail start --}}
    <div class="bg-white shadow-sm rounded-md space-y-5 mb-2 overflow-hidden">
        <table class="table-auto w-full text-left text-gray-500">
            <thead class='bg-slate-200'>
                <tr class='h-16'>
                    <th></th>
                    <th>Panini ID</th>
                    <th>Name</th>
                    <th>Team</th>
                    <th>MLB ID</th>
                    <th>Active</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="">
        @foreach ($players as $player)
                <tr class='h-12 even:bg-slate-50 hover:bg-slate-100'>
                    <td class='pl-3'>
                    </td>
                    <td>{{$player->panini_id}}</td>
                    <td>{{$player->player}}</td>
                    <td>
                        <div class="flex items-center">
                        {{$player->panini_team}}
                        </div>
                    </td>
                    <td class="text-green-500">{{$player->mlb_player_id}}</td>
                    <td>
                    @role('admin|auditor')
                        <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.mlb.change-player-id", {{json_encode(['new'=> false, 'player_id' => $player->id])}})'>Change MLB ID</button>
                    @endrole
                    </td>
                    <td>
                        {{-- more action dropdown list start --}}
                        {{-- @role('admin|auditor')
                        <div class="px-3">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="text-xl">•••</button>
                                </x-slot>
                                <x-slot name="content">
                                    <!-- Order management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        More Actions
                                    </div>
                                    <x-dropdown-link href="" x-on:click.prevent="" wire:click="reverseStatus({{$player->id}})">
                                        ↩︎ Undo Approval
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        @endrole --}}
                        {{-- more action dropdown list end --}}
                    </td>
                </tr>
        @endforeach
            </tbody>
        </table>
    </div>
    {{-- Showing none detail end --}}

    @if (count($players) == 0)
    <x-molecules.empty-content>
        No Data!
    </x-molecules.empty-content>
    @endif
    <div class="mb-5">
        {{ $players->withQueryString()->links() }}
    </div>
</div>