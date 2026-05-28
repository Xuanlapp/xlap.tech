
<div>
    <button wire:click="downloadData()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Get Data</button>
    <button wire:click="team_teams_match" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">fullname Match</button>
   <table class='table-auto'>
        <thead>
            <tr>
                <th class="pl-1">#</th>
                <th class="pl-1">Full name</th>
                <th class="pl-1">First name</th>
                <th class="pl-1">Last name</th>
                <th class="pl-1">Mid Name</th>
                <th class="pl-1">Player Id</th>
                <th class="pl-1">Active</th>
                <th class="pl-1">P. Team</th>
                <th class="pl-1">Panini Id</th>
                <th class="pl-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($players as $player)
                @php
                    // dd(gettype(json_decode($player->teams_played)));
                    $teams = json_decode($player->teams_played);
                @endphp
            <tr>
                <td class="pl-1">{{$player->id}}</td>
                <td class="pl-1">{{$player->panini_full_name}}</td>
                <td class="pl-1">{{$player->first_name}}</td>
                <td class="pl-1">{{$player->last_name}}</td>
                <td class="pl-1">{{$player->mid_name}}</td>
                <td class="pl-1">{{$player->player_id}}</td>
                <td class="pl-1">@if($player->active)Yes @else No @endif</td>
                <td class="pl-1">{{$player->panini_team}}</td>
                <td class="pl-1">{{$player->panini_id}}</td>
                <td class="pl-1"><button wire:click="downloadSingle({{$player->player_id}})" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Download</button></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-blue-400">
                    @foreach ($teams as $team)
                        {{$team}} |
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $players->withQueryString()->links() }}
</div>
