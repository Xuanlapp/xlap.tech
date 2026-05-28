<div>
    <div wire:loading>
        <div class="fixed top-0 left-0 w-full h-full flex items-center justify-center">
            <div class="absolute w-48 h-48 bg-white rounded-full flex items-center justify-center">
                <svg class="animate-spin h-16 w-16 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 016 12H2c0 2.981 1.657 5.623 4 7.016l-.001-1.725zm5.354-5.354A3 3 0 1115.243 15.9l2.828 2.828a5 5 0 10-7.071 0l2.828-2.828z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">Match Player</div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <div wire:key="item-{{ $player->panini_id }}" class="bg-slate-300 shadow-sm p-5 rounded-md hover:shadow-md space-y-5 text-lg">
            <div class="flex justify-between items-center space-x-3">
                <div class="text-3xl text-gray-400">Data From Panini</div>
                <div class="text-green-500 text-3xl flex-1">{{$player->panini_full_name}}</div>
                <div class="flex-1 flex items-center space-x-1">
                    <div class="text-gray-400">Play for: </div>
                    @if ($player->team->team_num !== null)
                        <img class="h-4 mr-2" src="{{"https://www.mlbstatic.com/team-logos/team-cap-on-light/".$player->team->team_num.".svg"}}" alt="">
                    @endif
                    <div>{{$player->panini_team}}</div>
                </div>
                <div><span class="text-gray-400">Panini ID: </span>{{$player->panini_id}}</div>
            </div>
        </div>
        <div class="text-3xl text-gray-300 text-center">Possible selections</div>
        <div class="grid grid-cols-4 gap-3">
            @foreach($match_players as $player)
            <div class="col-span-4 md:col-span-2 grid grid-cols-12 bg-white shadow-sm p-5 rounded-md hover:shadow-md">
                {{-- image and check box section start--}}
                <div class="col-span-5 flex-col content-between flex-none mr-5 space-y-3">
                    <img src="{{$player->player_img_url()}}" alt="" class="object-cover h-64">
                </div>
                <div class="col-span-7 flex-col items-stretch">
                    <div class="">
                        <div class="">
                            <p class="text-lg font-bold">{{$player->source_full_name}}</p>
                            <p><span class="text-gray-400">Name Given: </span>{{$player->source_name_given}}</p>
                        </div>
                        @if( $player->teams_played !== "[]")
                        <div><span class="text-gray-400">Teams Played: </span>
                            {{ implode(" | ", json_decode($player->teams_played)) }}
                        </div>
                        @endif
                        <div class="">
                            <span class="text-gray-400">MLB ID: </span> {{$player->mlb_player_id}}
                        </div>
                    </div>
                    <div class="flex h-48 items-end text-right">
                        <div class="flex-1">
                            <a href="http://mlb.com/search?q={{$player->source_first_name}}%20{{$player->source_last_name}}&playerId={{$player->mlb_player_id}}" target="popup" class="bg-blue-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-700 focus:outline-none focus:border-green-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.mlb.find-player-source"> 🔍 MLB.com</a>
                            <x-atomics.button-green class="item-end" wire:click="matchPlayer({{$player->mlb_player_id}})">Match</x-atomics.button-green>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
