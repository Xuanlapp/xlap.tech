<div class="grid grid-cols-12 bg-white shadow-sm p-5 rounded-md space-y-5 mb-2">
    {{-- Check box, image, player id section start testing--}}
    <div class="col-span-12 md:col-span-3 flex-col content-between flex-none mr-5 space-y-3">
        <div>
            <img src="{{$player->player_img_url()}}" alt="" class="object-cover h-64">
        </div>
        <div class="text-xl font-bold text-center text-green-600">
            <span class="text-gray-400">PANINI ID: </span> {{$player->panini_id}}
        </div>
        <div class="text-md text-center">
            <span class="text-gray-400">MLB ID: </span> {{$player->mlb_player_id}}
        </div>
    </div>
    {{-- Check box, image, player id section end --}}
    <div class="col-span-12 md:col-span-9 space-y-3">
        <div class="flex justify-between items-center">
            {{-- player active status section start --}}
            @if($player->active)
                <div class="bg-blue-100 p-3 flex-1">
                    <span class="text-yellow-600 font-bold">Active</span>
                </div>
            @else
                <div class="bg-gray-200 p-3 flex-1">
                    <span class="text-gray-400 font-bold">Retired</span>
                </div>
            @endif
            {{-- player active status section end --}}
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
                    {{$player->full_name}}
                </div>
                <div class="text-gray-400">
                    {{$player->first_name}} {{$player->last_name}}
                    @if ($player->middle_name != "")
                        ({{$player->middle_name}})
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
                <div>Played teams on MLB Record:
                    <div class="text-green-600">
                        {{ $player->show_teams_played() }}
                    </div>
                </div>
            </div>
            <div class="flex-1 mr-5">
                <div>Team on Panini Record:
                    <div class="text-green-600">
                        {{ $player->panini_team }}
                    </div>
                </div>
            </div>
            {{-- <div class="flex-1 mr-5">
                <div>Played Team:
                    <div class="text-green-600 text-2xl flex items-center">
                        @if ($player->team->team_num !== null)
                            <img class="h-8 mr-2" src="{{"https://www.mlbstatic.com/team-logos/team-cap-on-light/".$player->team->team_num.".svg"}}" alt="">
                        @endif
                        {{$player->team->team_name}}
                    </div>
                </div>
            </div> --}}
        </div>
        {{-- Player team playing section end --}}
        <hr class="mx-2">
        <div class="flex">
            <div class="flex-1 mr-5">
                <div>Position on MLB Record:
                    <div class="text-green-600">
                        {{ $player->position_name ."(".$player->position_abb .")" }}
                    </div>
                </div>
            </div>
            <div class="flex-1 mr-5">
                <div>Postion on Panini Record:
                    <div class="text-green-600">
                        {{ $player->panini_position }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Player stat showing section start --}}
    <div class="lg:col-start-3 col-span-12 col-start-1 overflow-x-auto">
        @if($player->stats->count())
            <div class="text-center font-bold text-gray-500 text-2xl py-10">MLB stats</div>
            <table class="table-auto w-full text-center">
                <thead>

                <tr>
                    <th class=""></th>
                    <th class=""></th>
                    @foreach ($player->show_stat_title() as $title_stat)
                        <th class="">{{$title_stat}}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @if($player->stats->count())
                    @foreach ($player->show_stat_with_quantity(20) as $year)
                        <tr class="">
                            @foreach ($year->show_stat() as $item)
                                <td class=""> {{$item}}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <th class="text-bold">Career</th>
                    <th class=""></th>
                    @foreach ($player->show_career() as $career_stat)
                        <td class="">{{$career_stat}}</td>
                    @endforeach
                </tr>
                </tbody>
            </table>
        @endif
    </div>
    <div class="lg:col-start-3 col-span-12 col-start-1 overflow-x-auto">

        @if($player->leaguestats->count())
            <div class="text-center font-bold text-gray-500 text-2xl py-10">League stats</div>
            <table class="table-auto w-full text-center">
                <thead>
                <tr>
                    <th class=""></th>
                    <th class=""></th>
                    @foreach($player->leaguestats->first()->get_stat_titless() as $title)

                        <th class="">{{ $title }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($player->leaguestats->sortByDesc('season')  as $stat)
                    <tr>
                        @foreach ($stat->show_stats() as $value)

                            <td class="">{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>

        @endif
    </div>


    {{-- Player stat showing section end --}}
</div>
