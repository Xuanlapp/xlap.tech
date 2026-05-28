<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    {{-- Filter start --}}
    <div class="flex bg-white shadow-sm rounded-md p-3">
        <div class="flex-1 w-full space-y-3">
            {{-- <div class="font-bold">Team</div>
            <x-molecules.select-list wire:model="selected_team" placeholder="Select Team">
                <option>Select Team</option>
                @foreach($teams as $item)
                    <option value="{{$item->team_name}}">
                        {{$item->team_name}}
                    </option>
                @endforeach
            </x-molecules.select-list> --}}
            <div class="font-bold">Search by Name</div>
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block md:w-1/3 w-full p-2.5"
                   type="text" wire:model="search_player" placeholder="Type player name here">
            {{-- <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='fetchData()'>Fetch Data</button> --}}
        </div>
    </div>

    <div class="my-5">
        {{ $players->withQueryString()->links() }}
    </div>

    <div class="bg-white shadow-sm rounded-md space-y-5 mb-2">

        <table class="table-auto w-full text-left text-gray-500">
            <thead class='bg-slate-200'>
            <tr class='h-16'>
                <th class="text-center">Panini ID</th>
                <th>Name</th>
                <th>Team</th>
                <th>NBA ID</th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody class="">
            @foreach ($players as $player)
                <tr class='h-12 even:bg-slate-50 hover:bg-slate-100'>
                    <td class="text-center">{{$player->panini_id}}</td>
                    <td>{{$player->player}}</td>
                    <td>
                        <div class="flex items-center">
                            {{$player->panini_team}}
                        </div>
                    </td>
                    <td>{{$player->nba_player_id}}</td>
                    <td class="">
                        {{-- Extra action buttons for each player start --}}
                        @role('admin|auditor')
                        <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"
                                wire:click='downloadStat({{$player->id}})'>
                            <x-icons.spinner wire:loading wire:target="downloadStat({{$player->id}})"
                                             class="fill-white h-5 mr-1"
                                             size="6"/>
                            Download Statssss
                        </button>
                        @endrole
                        {{-- Extra action buttons for each player start --}}
                    </td>
                    <td>

                        {{-- more action dropdown list start --}}
                        @role('admin|auditor')
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
                                    <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                       wire:click='$emit("openModal", "modals.nba.change-player-id", {{json_encode(['new'=> false, 'player_id' => $player->id])}})'>↩︎
                                        Change NBA ID</a>
                                    <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                       wire:click='$emit("openModal", "modals.mlb.confirm-dialog", {{json_encode(['message'=> 'Are you sure want to remove '. $player->panini_full_name.'?', 'player_id' => $player->id])}})'>❌
                                        Remove Player</a>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        @endrole

                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if (count($players) == 0)
        <x-molecules.empty-content>
            No Data!
        </x-molecules.empty-content>
    @endif
</div>