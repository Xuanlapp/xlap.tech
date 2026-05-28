<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    <div class="md:flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 md:space-x-10 space-y-3">
        {{-- First column filter start --}}
        <div class="space-y-3 md:basis-3/5">
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
                               class="py-3 ml-2 text-sm font-medium text-gray-900 dark:text-gray-500"> {{$key}}</label>
                    </div>
                @endforeach
            </div>

        </div>

        <div class="space-y-3 md:basis-2/5">
            <div class="font-bold flex space-x-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <span>Search by Team Name</span>
            </div>
            <input
                    class="border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    type="text" wire:model="search_player" placeholder="Type player name here">
        </div>
    </div>

    @role('admin')
    <div class="bg-white shadow-sm rounded-md p-3 space-x-3 flex">
        <div class="flex space-x-2">
            <div class="rounded-md space-y-5 mt-5 text-right ">
                <x-button wire:click="$emit('openModal', 'modals.logos.nba.add-logo-nba')">Add Team
                </x-button>
            </div>
        </div>
    </div>
    @endrole
    @if($selected_team !== '')
        <div class="h-64 flex space-x-5 items-center rounded-md shadow-sm"
             style="background: {{$selected_team_object->team_color()}}">
            @if($selected_team_object->team_abb !== null)
                <img class="ml-5 h-56" src='{{$selected_team_object->team_icon("D")}}' alt="">
            @endif
            <div class="text-white text-5xl font-extrabold p-5">{{Str::upper($selected_team_object->team_name)}} <span
                        class="text-lg">{{$selected_team_object->kind}}</span></div>
        </div>
    @endif

    <div class=" p-3 bg-white shadow-md rounded-md space-y-5 mt-3">
        <div class="my-5">
            {{ $logos->withQueryString()->links() }}
        </div>

        @foreach($logos as $logo)
            {{--            @dd($logos)--}}
            {{-- this ID is mandetory to have make the shortcut form work --}}
            <div class=" border p-4 rounded-md bg-slate-50 shadow-sm hover:bg-slate-100 hover:shadow-md space-y-3 relative cursor-pointer">

                <embed src="{{ asset('kdrive/basketball_logo_pdf/' . $logo->nba_team->pickup_name . '_' . $logo->begin . '_' . $logo->nba_team->init_letters . substr($logo->begin, 0, 1) . substr($logo->begin, 2) . 'A1.pdf')}}"
                       alt="Unrivaled"
                       class="w-24 mx-auto mt-4 mb-6"/>
                <div>{{$logo->nba_team->pickup_name . '_' . $logo->begin . '_' . $logo->nba_team->init_letters . substr($logo->begin, 0, 1) . substr($logo->begin, 2) . 'A1.pdf'}}</div>

                @if ($logo->nba_team )
                    <div class="flex">
                        <x-label>{{ $logo->nba_team->team_name }}</x-label>
                        <x-label class="ml-3"> parent_id: {{ $logo->nba_team->parent_id }}</x-label>
                    </div>
                @else
                    <x-label>Team not found</x-label>
                @endif
                <div class=" flex ">
                    <x-label>Year:</x-label>
                    <x-label class="ml-3">{{$logo->begin}}</x-label>
                    <x-label class="ml-3">{{$logo->end}}</x-label>
                </div>
            </div>
        @endforeach
        <div class="my-5">
            {{ $logos->withQueryString()->links() }}
        </div>
    </div>
</div>
