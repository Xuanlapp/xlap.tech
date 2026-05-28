<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    {{-- Filter start --}}
    <div class="font-bold mb-3 px-3">Filter</div>
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 ">
        {{-- First column filter start --}}
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                <span>Filter by Team</span>
            </div>
            <div class="flex">
                @foreach($team_kind as $key => $value)
                    <div class="flex items-center mr-5">
                        <input type="radio" value="{{$key}}" wire:model="kind" name="list-radio"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                        <label for="list-radio-license"
                               class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">{{$key}}</label>
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
        <div class="flex-col">
            <div class="flex items-center mr-5">
                <input type="radio" value="both" wire:model="active" name="active-radio"
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="list-radio-license"
                       class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">All Status</label>
            </div>
            <div class="flex items-center">
                <input type="radio" value="active" wire:model="active" name="active-radio"
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="list-radio-id"
                       class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Active</label>
            </div>
            <div class="flex items-center">
                <input type="radio" value="retired" wire:model="active" name="active-radio"
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                <label for="list-radio-millitary"
                       class="w-full py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Retired</label>
            </div>
        </div>
        {{-- Second column filter end --}}
        {{-- Third column filter start --}}
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <span>Search by Name</span>
            </div>
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                   type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
        {{-- Third column filter end --}}
    </div>
    {{-- Filter end --}}
    {{-- Widget action section start --}}
    @role('admin')
    <div class="bg-white shadow-sm rounded-md p-3 space-x-3 flex">
        <div class="text-3xl flex-none">
            🪄
        </div>
        <div class="space-y-3">
            <div>
                <input type="checkbox" wire:model="selectAll"
                       class="w-6 h-6 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"/>
                <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Check
                    all items on this page</label>
            </div>
            <div class="flex space-x-2">
                <button class="bg-red-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-700 focus:outline-none focus:border-red-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"
                        wire:click='$emit("openModal", "modals.mlb.update-player-stat", {{json_encode(['player_ids'=> $selectedItems])}})'>
                    <svg class="w-6 h-6" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor"
                         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"></path>
                    </svg>
                    <span>Update Checked Players</span>
                </button>
            </div>
        </div>
    </div>
    @endrole

    {{-- Pagination Start --}}
    <div class="my-5">
        {{ $players->withQueryString()->links() }}
    </div>
    {{-- Pagination end --}}

    @if (!$detail)
        {{-- Showing none detail start --}}
        <div class="bg-white shadow-sm rounded-md space-y-5 mb-2">
            <table class="table-auto w-full text-left text-gray-500">
                <thead class='bg-slate-200'>
                <tr class='h-16'>
                    <th></th>
                    <th>Panini ID</th>
                    <th>Name</th>
                    <th>Last Played Team</th>
                    <th>Latest Season</th>
                    <th>MLB ID</th>
                    <th>Active</th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="">
                @foreach ($players as $player)
                    <tr class='h-12 even:bg-slate-50 hover:bg-slate-100'>
                        <td class='pl-3'>
                            <input type="checkbox" wire:model="selectedItems" value="{{$player->id}}"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </td>
                        <td>{{$player->panini_id}}</td>
                        <td>
                            <a class="cursor-pointer hover:text-blue-800 hover:underline text-blue-500"
                               wire:click='$emit("openModal", "modals.mlb.player-detail", {{json_encode(['player_id' => $player->id])}})'>
                                {{$player->player}}
                            </a>
                        </td>
                        {{-- <td>
                            <div class="flex items-center">
                            @if ($player->team->team_num !== null)
                                <img class="h-4 mr-2" src="{{"https://www.mlbstatic.com/team-logos/team-cap-on-light/".$player->team->team_num.".svg"}}" alt="">
                            @endif
                            {{$player->panini_team}}
                            </div>
                        </td> --}}
                        <td>
                            <div class="flex space-x-3 items-center"><img src="{{$player->team_icon()}}" class="w-6"
                                                                          alt="">
                                <div>{{$player->last_played_team}}</div>
                            </div>
                        </td>
                        <td>{{$player->stats->count()?$player->stats->last()->season:""}}</td>
                        <td class="text-green-500"><a href="https://www.mlb.com/player/{{$player->mlb_player_id}}"
                                                      class="hover:underline"
                                                      target="_blank">{{$player->mlb_player_id}}</a></td>
                        <td>
                            @if($player->active)
                                <span class="text-yellow-600 font-bold">Active</span>
                            @else
                                <span class="text-gray-400 font-bold">Retired</span>
                            @endif
                        </td>
                        <td>
                            <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150"
                                    wire:click='$emit("openModal", "modals.mlb.player-detail", {{json_encode(['player_id' => $player->id])}})'>
                                Detail
                            </button>
                        </td>
                        <td class="">
                            {{-- Extra action buttons for each player start --}}
                            @role('admin')
                            {{-- @if ($player->active == true && $player->arch_career_stat == null)
                            <x-atomics.button-danger wire:click="updateSingleStat({{$player->id}})"><span class="text-sm">⚡️</span> Update</x-atomics.button-danger>
                            @endif --}}
                            {{-- @if ($player->arch_career_stat !== null)
                            <x-atomics.button-green wire:click="updateSingleStat({{$player->id}})"><span class="text-sm">🛟</span> Retrieve</x-atomics.button-green>
                            @endif --}}
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
                                        <x-dropdown-link href="" x-on:click.prevent=""
                                                         wire:click="reverseStatus({{$player->id}})">
                                            ↩︎ Undo Approval
                                        </x-dropdown-link>
                                        <x-dropdown-link href="" x-on:click.prevent=""
                                                         wire:click="checkLeagueStats({{$player->mlb_player_id}})">
                                            ↩︎ Check league stats
                                        </x-dropdown-link>
                                        <a class="block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                           wire:click='$emit("openModal", "modals.mlb.change-player-id", {{json_encode(['new'=> true, 'player_id' => $player->id])}})'>🆔
                                            Change ID</a>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                            @endrole
                            {{-- more action dropdown list end --}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{-- Showing none detail end --}}
    @else
        {{-- Showing detail in card start --}}
        @foreach ($players as $player)
            <div class="grid grid-cols-12 bg-white shadow-sm p-5 rounded-md hover:shadow-md space-y-5 mb-2">
                {{-- Check box, image, player id section start --}}
                <div class="col-span-12 md:col-span-2 flex-col content-between flex-none mr-5 space-y-3">
                    <input type="checkbox" wire:model="selectedItems" value="{{$player->id}}"
                           class="w-8 h-8 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <img src="{{$player->player_img_url()}}" alt="" class="object-cover h-64">
                    <div class="text-xl font-bold text-center text-green-600">
                        <span class="text-gray-400">PANINI ID: </span> {{$player->panini_id}}
                    </div>
                    <div class="text-md text-center">
                        <span class="text-gray-400">MLB ID: </span> {{$player->mlb_player_id}}
                    </div>
                </div>
                {{-- Check box, image, player id section end --}}
                <div class="col-span-12 md:col-span-10 space-y-3">
                    <div class="flex justify-between items-center">
                        {{-- player active status section start --}}
                        @if($player->active)
                            <div class="bg-blue-100 p-3 flex-1">Active:
                                <span class="text-yellow-600 font-bold">Yes</span>
                            </div>
                        @else
                            <div class="bg-gray-200 p-3 flex-1">Active:
                                <span class="text-gray-400 font-bold">No</span>
                            </div>
                        @endif
                        {{-- player active status section end --}}
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
                                    <x-dropdown-link href="" x-on:click.prevent=""
                                                     wire:click="reverseStatus({{$player->id}})">
                                        ↩︎ Undo Approval
                                    </x-dropdown-link>
                                    <a class="block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                       wire:click='$emit("openModal", "modals.mlb.change-player-id", {{json_encode(['new'=> true, 'panini_id' => $player->panini_id])}})'>🆔
                                        Change ID</a>
                                </x-slot>
                            </x-dropdown>
                        </div>
                        @endrole
                        {{-- more action dropdown list end --}}
                    </div>
                    <div class="flex">
                        <div class="flex-1 font-bold">From MLB data</div>
                        <div class="flex-1 font-bold">From Panini data</div>
                    </div>
                    <hr class="mx-2">
                    {{-- Player name matching section start --}}
                    <div class="flex">
                        <div class="flex-1">
                            <div class="text-2xl text-green-600">
                                {{$player->first_name}} {{$player->last_name}}
                                @if ($player->mid_name != "")
                                    ({{$player->mid_name}})
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="text-green-600 text-2xl">{{$player->player}}</div>
                        </div>
                    </div>
                    {{-- Player name matching section end --}}
                    <hr class="mx-2">
                    {{-- Player team playing section start --}}
                    <div class="flex">
                        <div class="flex-1 mr-5">
                            <div>Played teams:
                                <div class="text-green-600">
                                    {{ $player->show_teams_played() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div><span class="text-gray-400">Team Match:</span>
                        @if($player->any_team_match)
                            <span class="text-green-600 font-bold">Yes</span>
                        @else
                            <span class="text-gray-400 font-bold">No</span>
                        @endif
                    </div>
                    {{-- Player team playing section end --}}
                    <hr class="mx-2">
                </div>
                {{-- Player stat showing section start --}}
                <div class="lg:col-start-3 col-span-12 col-start-1 overflow-x-auto">
                    @if($player->stats !== "[]")
                        <table class="table-auto w-full text-center">
                            <thead>
                            <tr>
                                <th class=""></th>
                                <th class=""></th>
                                @foreach ($player->show_stats()['title'] as $title_stat)
                                    <th class="">{{$title_stat}}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($player->show_stats()['oneYear'] as $year)
                                <tr class="">
                                    @foreach ($year as $item)
                                        <td class=""> {{$item}}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr>
                                @foreach ($player->show_stats()['career'] as $career_stat)
                                    <td class="">{{$career_stat}}</td>
                                @endforeach
                            </tr>
                            </tbody>
                        </table>
                    @endif
                </div>
                {{-- Player stat showing section end --}}
                {{-- Extra action buttons for each player start --}}
                @role('admin')
                <div class="col-span-12 flex justify-end space-x-5">
                    @if ($player->active == true && $player->arch_career_stat == null)
                        <x-atomics.button-danger wire:click="updateSingleStat({{$player->id}})"><span
                                    class="text-xl">⚡️</span> Update Data
                        </x-atomics.button-danger>
                    @endif
                    @if ($player->arch_career_stat !== null)
                        <x-atomics.button-green wire:click="updateSingleStat({{$player->id}})"><span
                                    class="text-xl">🛟</span> Retrieve Archive
                        </x-atomics.button-green>
                        <x-atomics.button-disabled disabled><span class="text-xl">⚡️</span> Update Data
                        </x-atomics.button-disabled>
                    @endif
                </div>
                @endrole
                {{-- Extra action buttons for each player start --}}
            </div>
        @endforeach
    @endif
    @if (count($players) == 0)
        <x-molecules.empty-content>
            No Data!
        </x-molecules.empty-content>
    @endif
    <div class="mb-5">
        {{ $players->withQueryString()->links() }}
    </div>
</div>
