<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    {{-- Excel Import Section --}}
    <div class="bg-white shadow-sm rounded-md p-5 mb-5">
        <div class="font-bold flex space-x-1 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span>Import Excel File</span>
        </div>
        
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <input type="file" wire:model="excel_file" accept=".xlsx,.xls" 
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('excel_file') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <button wire:click="importExcel" 
                    wire:loading.attr="disabled" 
                    wire:target="importExcel"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50">
                <span wire:loading.remove wire:target="importExcel">Import Excel</span>
                <span wire:loading wire:target="importExcel">Importing...</span>
            </button>
        </div>
        
        <div class="mt-2 text-sm text-gray-600">
            <p>Support format: .xlsx, .xls | Max file size: 10MB</p>
            <p>Support multiple sheets import. Each sheet name will be used as the team name, player name in the first column, and statistics data in the table format.</p>
            <p>Players with "Retired" in the sheet name will be marked as retired, and players with "Rookies" will be marked as rookies.</p>
        </div>
    </div>

    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-10">
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
    @if($selected_team !== '')
    <div class="h-64 flex space-x-5 items-center rounded-md shadow-sm" style="background: {{$selected_team_object->team_color()}}">
        @if($selected_team_object->team_abb !== null)
        <img class="ml-5 h-56" src='{{$selected_team_object->team_icon("D")}}' alt="">
        @endif
        <div class="text-white text-5xl font-extrabold p-5">{{Str::upper($selected_team_object->team_name)}} <span class="text-lg">{{$selected_team_object->kind}}</span></div>
    </div>
    @endif
    
    {{-- WNBA Players Section --}}
    <div class="mt-3 shadow-sm rounded-md p-3 space-x-3 bg-white">
        <div class="font-bold text-lg mb-3">WNBA Players List</div>
        @if(isset($players) && $players->count() > 0)
            <div class="my-5">
                {{ $players->withQueryString()->links() }}
            </div>
            <div class="bg-white shadow-sm rounded-md space-y-5 mb-2 overflow-hidden">
                <table class="table-auto w-full text-left text-gray-500">
                    <thead class="bg-slate-200">
                        <tr class="h-16">
                            <th class="pl-3">Player</th>
                            <th>Team</th>
                            <th>Position</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th>Retired</th>
                            <th>Stats</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($players as $player)
                        <tr class='h-12 even:bg-slate-50 hover:bg-slate-100'>
                            <td class="pl-3 font-semibold">{{$player->player}}</td>
                            <td>{{$player->team}}</td>
                            <td>{{$player->full_pos}}</td>
                            <td>{{$player->team_year}}</td>
                            <td>
                                @if($player->status)
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">{{$player->status}}</span>
                                @endif
                            </td>
                            <td>
                                @if($player->retire === 'Y')
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Retired</span>
                                @else
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>
                                @endif
                            </td>
                            <td>
                                @if($player->stat && is_array($player->stat) && count($player->stat) > 0)
                                    <span class="text-blue-600">{{ count($player->stat) }} Seasons</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="my-5">
                {{ $players->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>No WNBA players data</p>
                <p class="text-sm">Please use the import function above to add player data</p>
            </div>
        @endif
    </div>
</div>
