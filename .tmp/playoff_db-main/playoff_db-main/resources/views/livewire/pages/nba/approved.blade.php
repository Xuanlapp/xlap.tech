<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Filters Section --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Team Filter --}}
            <div class="space-y-4">
                <div class="flex items-center space-x-2 text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    <span class="font-semibold">Filter by Team</span>
                </div>
                
                <div class="flex flex-wrap gap-4">
                    @foreach($team_kind as $key => $value)
                    <label class="inline-flex items-center">
                        <input type="radio" value="{{$key}}" wire:model="kind" name="list-radio"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">{{$key}}</span>
                    </label>
                    @endforeach
                </div>

                <select wire:model="selected_team"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Team</option>
                    @foreach($team_kind["{$kind}"] as $item)
                    <option value="{{$item->team_name}}">
                        {{$item->team_name.' - '. $item->kind}}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Search Filter --}}
            <div class="space-y-4">
                <div class="flex items-center space-x-2 text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="font-semibold">Search by Name</span>
                </div>
                <input type="text" wire:model="search_player" 
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Type player name here">
            </div>
        </div>
    </div>

    {{-- Admin Actions --}}
    @role('admin')
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <span class="text-xl">🪄</span>
                </div>
            </div>
            <div class="flex-1 space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" wire:model="selectAll"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">Select all items on this page</label>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click='$emit("openModal", "modals.nba.update-player-stat", {{json_encode(['player_ids'=> $selectedItems])}})'
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        Update Selected Players
                    </button>
                    <div class="relative group">
                        <button wire:click='$emit("openModal", "modals.nba.import-college-stats")'
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="mr-2">📁</span>
                            Import Stats
                        </button>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs text-white bg-gray-700 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            Import Excel College Stats
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        @if (session()->has('success'))
            <div class="mt-4 p-4 rounded-lg bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mt-4 p-4 rounded-lg bg-red-50 text-red-700">
                {{ session('error') }}
            </div>
        @endif
    </div>
    @endrole

    {{-- Players Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            {{ $players->withQueryString()->links() }}
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kind</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Count</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Season</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Season</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">NBA ID</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Panini ID</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($players as $player)
                    <tr class="hover:bg-gray-50" wire:key="player-{{ $player->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" wire:model="selectedItems" value="{{$player->id}}"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{$player->id}}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click='$emit("openModal", "modals.nba.player-detail", {{json_encode(['player_id' => $player->id])}})'
                                class="flex items-center space-x-2 text-sm text-gray-900 hover:text-blue-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>{{$player->player}}</span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                @if($player->team_icon('L'))
                                    <img class="h-6 w-6" src="{{$player->team_icon('L')}}" alt="">
                                @endif
                                <span class="text-sm text-gray-900">{{$player->team_name}}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{$player->team_kind()}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="font-medium">{{$player->stats->filter(function ($item) { return $item->extra == 0; })->count()}}</span>
                            year{{$player->stats->filter(function ($item) { return $item->extra == 0; })->count() > 1 ? 's' : ''}}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{$player->stats->count()?$player->stats->first()->year:""}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{$player->stats->count()?$player->stats->last()->year:""}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">{{$player->nba_player_id}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">{{$player->panini_id}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @role('admin|auditor')
                            <div class="relative">
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="text-gray-400 hover:text-gray-500">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <div class="py-1">
                                            <a wire:click='$emit("openModal", "modals.nba.change-player-id", {{json_encode(['new'=> false, 'player_id' => $player->id])}})'
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Change NBA ID
                                            </a>
                                            <a wire:click='$emit("openModal", "modals.nba.player-detail", {{json_encode(['player_id' => $player->id])}})'
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                View Details
                                            </a>
                                        </div>
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

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $players->withQueryString()->links() }}
        </div>
    </div>
</div>
