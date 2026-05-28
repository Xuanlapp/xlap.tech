<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    {{-- <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-10">
        <x-atomics.button-danger wire:click="customFunction()">
            run
        </x-atomics.button-danger>
    </div> --}}

    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-10">
        {{-- First column filter start --}}
        <div class="flex-1 w-full space-y-3">
            <div class="font-bold">Search by Team</div>
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
            <div class="font-bold">Search by Name</div>
            <input class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
        {{-- Second column filter end --}}
    </div>
    @if($selected_team !== '')
    <div class="h-64 flex space-x-5 items-center rounded-md shadow-sm" style="background: {{$selected_team_object->team_color()}}">
        @if($selected_team_object->team_abb !== null)
        <img class="ml-5 h-56" src='{{$selected_team_object->team_icon("D")}}' alt="">
        @endif
        <div class="text-white text-5xl font-extrabold p-5">{{Str::upper($selected_team_object->team_name)}} <span class="text-lg">{{$selected_team_object->kind}}</span></div>
    </div>
    @endif

    <div class="mt-3 shadow-sm rounded-md p-3 space-x-3 bg-white">
        <div class="my-5">
            {{ $players->withQueryString()->links() }}
        </div>
        <div class="bg-white shadow-sm rounded-md space-y-5 mb-2 overflow-hidden">
            <table class="table-auto w-full text-left text-gray-500" >
                <thead class="bg-slate-200">
                    <tr class="h-16">
                        <th class="pl-3">Player</th>
                        <th>Team</th>
                        <th>Kind</th>
                        <th>Year Count</th>
                        <th>First Season</th>
                        <th>Latest Season</th>
                        <th class=" text-center">Games</th>
                        <th class=" text-center">Panini ID</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($players as $player)
                    <tr class='h-12 even:bg-slate-50 hover:bg-slate-100'>
                        <td class="pl-3">{{$player->player}}</td>
                        <td class="h-10 flex items-center space-x-2">
                            @if($player->team_icon('L'))
                            <img class='w-8 h-8' src="{{$player->team_icon('L')}}" alt="">
                            @endif
                            <span>{{$player->team_name}}</span>
                        </td>
                        <td>{{$player->team_kind()}}</td>
                        <td><span class="font-bold">{{$player->stats->filter(function ($item) { return $item->extra == 0; })->count()}}</span> year{{
                        $player->stats->filter(function ($item) { return $item->extra == 0; })->count() > 1 ? 's' : '' }}</td>
                        <td>{{$player->stats->count()?$player->stats->first()->year:""}}</td>
                        <td>{{$player->stats->count()?$player->stats->last()->year:""}}</td>
                        <td class=" text-center">{{$player->g}}</td>
                        <td class=" text-center">{{$player->panini_id}}</td>
                        <td>
                        @role('admin|auditor')
                            <button class="bg-green-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.nba.player-detail", {{json_encode(['player_id' => $player->id])}})'>Detail</button>
                        @endrole
                        </td>
                        <td></td>
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
