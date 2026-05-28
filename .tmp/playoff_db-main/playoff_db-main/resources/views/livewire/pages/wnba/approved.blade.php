<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    <div class="md:flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 md:space-x-10 space-y-3">
        {{-- Second column filter start --}}
        <div class="space-y-3 md:basis-2/5">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <span>Search by Name</span>
            </div>
            <input
                class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
        {{-- Second column filter end --}}
    </div>

    <div class="mt-3 shadow-sm rounded-md p-3 space-x-3 bg-white">
        <div class="my-5">
            {{ $players->withQueryString()->links() }}
        </div>

        <div class="bg-white shadow-sm rounded-md space-y-5 mb-2 overflow-hidden">
            <table class="table-auto w-full text-left text-gray-500">
                <thead class="bg-slate-200">
                <tr class="h-16">
                    <th></th>
                    <th>ID</th>
                    <th class="pl-3">Player</th>
                    <th>Team</th>
                    <th>Position</th>
                    <th>Year Count</th>
                    <th>Retire</th>
                    <th>Status</th>
                    <th class=" text-center">Panini ID</th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($players as $player)
                    <tr class='h-12 even:bg-slate-50 hover:bg-slate-100' wire:key="player-{{ $player->id }}">
                        <td class='pl-3'>
                            <input type="checkbox" wire:model="selectedItems" value="{{$player->id}}"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </td>
                        <td>{{$player->id}}</td>
                        <td class="pl-3 hover:cursor-pointer hover:text-blue-500 hover:font-bold"
                            wire:click='$emit("openModal", "modals.wnba.player-detail", {{json_encode(['player_id' => $player->id])}})'>
                            <div class="flex space-x-2 ">
                                <x-icons.magnify-glass class="h-5 mr-1"/>
                                <div>{{$player->player}}</div>
                            </div>
                        </td>
                        <td class="h-10 flex items-center space-x-2">
                            <span>{{$player->team}}</span>
                        </td>
                        <td>{{$player->full_pos}}</td>
                        <td>{{$player->team_year}}</td>
                        <td>{{$player->retire}}</td>
                        <td>{{$player->status}}</td>
                        <td class=" text-center">{{$player->panini_id}}</td>
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
