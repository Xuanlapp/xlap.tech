<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    <div class="flex bg-white justify-between items-end shadow-sm rounded-md p-5 mb-2 mx-auto space-x-5">
        <div class="flex flex-col space-y-3 w-64 flex-initial">
            <div class="font-bold flex space-x-1 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="#545454" class="w-6 h-6" @>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                </svg>
                <span>Filter by Leagues</span>
            </div>
            <div class="flex-1">
                {{-- <x-molecules.select-list-modal :data="$team_kind" placeholder="Sort by Team" wire:model="kind"/> --}}
                <select wire:model="kind"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">All Leagues</option>
                    @foreach($team_kind as $key => $value)
                        <option value="{{$key}}">{{$key}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-atomics.search-input
                model="search"
                placeholder="Search by Team Name"
                class="w-72"
        />
    </div>

    @if($kind !==   "")
        <div class="text-4xl font-bold shadow-sm rounded-md p-5 bg-white text-blue-500 text-center">
            <span class="font-normal text-xl text-gray-400">Filtered by</span> {{$kind}}
        </div>
    @endif

    <div class="p-3 bg-white shadow-md rounded-md space-y-5 mt-3">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-4 space-y-4">
                @role('admin')
                <div class="rounded-md space-y-5 mt-5 text-left">
                    <x-button wire:click="$emit('openModal', 'modals.logos.nba.add-logo-nba')">Add Team</x-button>
                </div>
                @endrole
                <div class="h-[1050px] overflow-y-auto p-3 border-gray-100 border-2 rounded-md">
                    @foreach($teams as $team)
                        <div
                                wire:click="selectLogo({{ $team->id ?? null }})"
                                x-data="{}"
                                @click="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="border p-4 rounded-md bg-slate-50 shadow-sm hover:bg-slate-100 hover:shadow-md space-y-3 relative cursor-pointer mb-2
                                {{ $selectedLogoId == $team->id ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}"
                                id="team-{{ $team->id }}"
                        >
                            <div class="flex flex-col justify-between">
                                <div>
                                    <x-label
                                            class="font-semibold text-xl {{ $selectedLogoId == $team->id ? 'text-blue-600' : '' }}">
                                        {{ $team->team_name ?? 'Unknown Team' }}
                                    </x-label>
                                </div>
                                <div class="text-sm text-gray-400">
                                    {{ $team->kind ?? '--' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-span-8 flex-col flex space-y-3">
                @if($selectedLogoId)
                    @role('admin')
                    <div class="rounded-md space-y-5 mt-5 text-right">
                        @if($selectedLogoDetails && $selectedLogoDetails->first() && $selectedLogoDetails->first()->team_id)
                            <x-button
                                    wire:click="$emit('openModal', 'modals.logos.nba.add-logo-version',{{json_encode(['team_id'=>$selectedLogoDetails->first()->team_id,'status'=>'add'])}})">
                                Add Version
                            </x-button>
                        @endif
                    </div>
                    @endrole
                    @if($selectedLogoDetails && $selectedLogoDetails->count() > 0)
                        <div class="flex items-center justify-center space-x-2">
                            <div class="text-4xl font-bold">
                                {{$selectedLogoDetails->first()->nba_team->team_name}}
                            </div>
                            <div class="bg-blue-500 text-white p-2 rounded-sm">
                                {{$selectedLogoDetails->first()->nba_team->kind}}
                            </div>
                        </div>
                        <div class="space-y-3 bg-blue-100 p-5 rounded-md">
                            <div class="justify-end">
                                <button
                                        wire:click="$emit('openModal', 'modals.logos.nba.add-logo-nba',{{json_encode(['team_nba_id'=>$selectedLogoDetails->first()->nba_team->id,'status'=>'edit'])}})"
                                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-gray-100 hover:bg-blue-500 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                                    <img src="{{asset('icons/edit.svg')}}" alt="" class="w-5 h-5"/>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="font-semibold text-white text-3xl text-center">Team Information</div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                    <x-slot name="description">Team Name Only</x-slot>
                                    <x-slot name="item">
                                        {{ $selectedLogoDetails->first()->nba_team->stat_name ?? 'N/A' }}
                                    </x-slot>
                                </x-atomics.desc-field>
                                <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                    <x-slot name="description">Team Abbreviation</x-slot>
                                    <x-slot name="item">
                                        {{ strtoupper($selectedLogoDetails->first()->nba_team->team_abb) ?? 'N/A' }}
                                    </x-slot>
                                </x-atomics.desc-field>
                                <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                    <x-slot name="description">Team City Name</x-slot>
                                    <x-slot name="item">
                                        {{ $selectedLogoDetails->first()->nba_team->city ?? 'N/A' }}
                                    </x-slot>
                                </x-atomics.desc-field>
                                @if($selectedLogoDetails->first()->nba_team->parent_team && $selectedLogoDetails->first()->nba_team->parent_id != $selectedLogoDetails->first()->nba_team->id)
                                    <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                        <x-slot name="description">Current Team</x-slot>
                                        <x-slot name="item">
                                            <a wire:click="selectLogo({{ $selectedLogoDetails->first()->nba_team->parent_team->id ?? null }})"
                                               class="hover:text-blue-800 hover:underline text-blue-500 text-xl font-bold  cursor-pointer">{{ $selectedLogoDetails->first()->nba_team->parent_team->team_name }}</a>
                                        </x-slot>
                                    </x-atomics.desc-field>
                                @endif
                                @if($selectedLogoDetails->first()->nba_team->related_teams->count() > 0)
                                    <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                        <x-slot name="description">Related Team(s)</x-slot>
                                        <x-slot name="item">
                                            <div class="flex flex-wrap gap-x-3">
                                                @foreach($selectedLogoDetails->first()->nba_team->related_teams as $related_team)
                                                    <a wire:click="selectLogo({{ $related_team->id ?? null }})"
                                                       class=" hover:text-blue-800 hover:underline text-blue-500 text-xl
                                                       font-bold cursor-pointer">
                                                        {{ $related_team->team_name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </x-slot>
                                    </x-atomics.desc-field>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-4 mt-4">
                            @foreach($selectedLogoDetails as $logo)
                                <div class="flex flex-col border p-4 rounded-md bg-slate-50 shadow-sm space-y-3">
                                    <div class="flex justify-between">
                                        <button
                                                wire:click="$emit('openModal', 'modals.logos.nba.add-logo-version',{{json_encode(['team_id'=>$logo->team_id,'status'=>'edit','version_id'=>$logo->id])}})"
                                                class="flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-gray-100 hover:bg-blue-500 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                                            <img src="{{asset('icons/edit.svg')}}" alt="" class="w-5 h-5"/>
                                            <span>Edit</span>
                                        </button>
                                        <button
                                                wire:click="$emit('openModal', 'modals.logos.nba.check-logo-version',{{json_encode(['version'=>$logo])}})"
                                                class="flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-gray-100 hover:bg-blue-500 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                                <path d="M22 11.0857V12.0057C21.9988 14.1621 21.3005 16.2604 20.0093 17.9875C18.7182 19.7147 16.9033 20.9782 14.8354 21.5896C12.7674 22.201 10.5573 22.1276 8.53447 21.3803C6.51168 20.633 4.78465 19.2518 3.61096 17.4428C2.43727 15.6338 1.87979 13.4938 2.02168 11.342C2.16356 9.19029 2.99721 7.14205 4.39828 5.5028C5.79935 3.86354 7.69279 2.72111 9.79619 2.24587C11.8996 1.77063 14.1003 1.98806 16.07 2.86572M22 4L12 14.01L9 11.01"
                                                      stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                      stroke-linejoin="round"></path>
                                            </svg>
                                            <span>Check Logo Version</span>
                                        </button>

                                    </div>

                                    <div class="text-3xl font-bold text-blue-500">
                                        {{$logo->begin}} - @if($logo->end!==3000)
                                            {{$logo->end}}
                                        @else
                                            Present
                                        @endif
                                    </div>
                                    {{-- Display Logo show case --}}
                                    <div class="flex gap-3 overflow-scroll">
                                        <x-atomics.team-logo-display :logo="$logo" title="Preview"
                                                                     version="{{$logo->on_white}}">
                                            <div class="flex p-0 w-[250px] h-[250px]">
                                                <div class="bg-gray-100 w-[125px] h-[250px]"></div>
                                                <div class="bg-gray-700 w-[125px] h-[250px]"></div>
                                            </div>
                                            <x-slot name="footer">
                                                <div class="mt-1 text-sm text-gray-500">On White</div>
                                            </x-slot>
                                        </x-atomics.team-logo-display>
                                        <x-atomics.team-logo-display :logo="$logo" title="Preview"
                                                                     version="{{$logo->on_black}}">
                                            <div class="flex p-0 w-[250px] h-[250px]">
                                                <div class="bg-gray-100 w-[125px] h-[250px]"></div>
                                                <div class="bg-gray-700 w-[125px] h-[250px]"></div>
                                            </div>
                                            <x-slot name="footer">
                                                <div class="mt-1 text-sm text-gray-500">On Black</div>
                                            </x-slot>
                                        </x-atomics.team-logo-display>
                                        <x-atomics.team-logo-display :logo="$logo" :tc-number="1" title="Primary TC"
                                                                     version="{{$logo->on_primary}}">
                                            <x-slot name="footer">
                                                <div class="mt-1 text-sm text-gray-500">Primary TC1</div>
                                            </x-slot>
                                        </x-atomics.team-logo-display>
                                        <x-atomics.team-logo-display :logo="$logo" :tc-number="2" title="Secondary TC"
                                                                     version="{{$logo->on_secondary}}">
                                            <x-slot name="footer">
                                                <div class="mt-1 text-sm text-gray-500">Secondary TC2</div>
                                            </x-slot>
                                        </x-atomics.team-logo-display>
                                    </div>
                                    {{-- Wordmark showcase --}}
                                    @php
                                        $WordmarkFilePath = 'kdrive/NBA_Wordmark/'.$logo->wordmark_path();
                                        $fileExists = file_exists(public_path($WordmarkFilePath));
                                    @endphp
                                    <div class="flex ">
                                        @if($fileExists)
                                            <div class="flex flex-col">
                                                <iframe
                                                        src="{{asset('kdrive/NBA_Wordmark/'.$logo->wordmark_path())}}#toolbar=0&navpanes=0&scrollbar=0&view=FitH&zoom=page-fit&transparent=1"
                                                        width="250px"
                                                        height="250px"
                                                        style="border: none; background-color: transparent; overflow: hidden;"
                                                        allowfullscreen
                                                        scrolling="no"
                                                ></iframe>
                                                <div class="mt-1 text-sm text-gray-500 text-center">Wordmark</div>
                                            </div>
                                        @else
                                            <div class="flex flex-col">
                                                <div class="flex items-center justify-center  bg-slate-100 w-[250px] h-[250px]">
                                                    <div class="text-gray-700 text-lg font-bold">No Wordmark</div>
                                                </div>
                                                <div class="mt-1 text-sm text-gray-500 text-center">Wordmark</div>
                                            </div>
                                        @endif


                                        <div class="flex flex-col justify-center items-end flex-1 space-y-4">
                                            <div class="flex justify-between items-center">
                                                <x-label class="text-gray-900 text-center mr-1">
                                                    On White
                                                </x-label>
                                                <x-label
                                                        class="text-lg text-center text-gray-900 border rounded-md p-2 shadow-sm bg-yellow-100">
                                                    {{$logo->on_white}}
                                                </x-label>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <x-label class="text-gray-900 text-center mr-1">
                                                    On Black
                                                </x-label>
                                                <x-label
                                                        class="text-lg text-center text-gray-900 border rounded-md p-2 shadow-sm bg-yellow-100">
                                                    {{$logo->on_black}}
                                                </x-label>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <x-label class="text-gray-900 text-center mr-1">
                                                    On Primary
                                                </x-label>
                                                <x-label
                                                        class="text-lg text-center text-gray-900 border rounded-md p-2 shadow-sm bg-yellow-100">
                                                    {{$logo->on_primary}}
                                                </x-label>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <x-label class="text-gray-900 text-center mr-1">
                                                    On Secondary
                                                </x-label>
                                                <x-label
                                                        class="text-lg text-center text-gray-900 border rounded-md p-2 shadow-sm bg-yellow-100">
                                                    {{$logo->on_secondary}}
                                                </x-label>
                                            </div>
                                        </div>
                                    </div>

                                    <x-label class=" text-gray-900  mt-16">
                                        Logo Version Information
                                    </x-label>

                                    @php
                                        $versions = json_decode($logo->logo_version_json);
                                    @endphp
                                    <div class="flex gap-3 overflow-scroll ">
                                        @foreach($versions as $version)
                                            <x-atomics.team-logo-version :logo='$logo' :version='$version'
                                                                         title="Preview">
                                                <div class="flex p-0 w-[150px] h-[150px]">
                                                    <div class="bg-gray-100 w-[75px] h-[150px]"></div>
                                                    <div class="bg-gray-700 w-[75px] h-[150px]"></div>
                                                </div>
                                                <x-slot name="footer">
                                                    <div class="mt-1 text-sm text-gray-500">{{$version}}</div>
                                                </x-slot>
                                            </x-atomics.team-logo-version>
                                        @endforeach
                                    </div>
                                    <div class="flex gap-3">
                                        <x-atomics.desc-field class="flex-1" item-class="font-bold">
                                            <x-slot name="description">File Name</x-slot>
                                            <x-slot name="item">
                                                {{ $logo->logo_file_name() ?? 'N/A' }}
                                            </x-slot>
                                        </x-atomics.desc-field>
                                        <x-atomics.desc-field class="" item-class="font-bold">
                                            <x-slot name="description">CMYK Pri</x-slot>
                                            <x-slot name="item">
                                                {{ $logo->pri_tc ?? 'N/A' }}
                                            </x-slot>
                                        </x-atomics.desc-field>
                                        <x-atomics.desc-field class="" item-class="font-bold">
                                            <x-slot name="description">CMYK Sec</x-slot>
                                            <x-slot name="item">
                                                {{ $logo->sec_tc ?? 'N/A' }}
                                            </x-slot>
                                        </x-atomics.desc-field>
                                    </div>

                                    <div class="flex justify-between">

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-label class="text-center text-gray-900 border rounded-md p-4 shadow-sm mt-16">
                            No Versions found for this team
                        </x-label>
                    @endif
                @else
                    <x-label
                            class="text-lg text-center text-gray-900 border rounded-md p-4 shadow-sm mt-16">
                        Select a team to view Versions
                    </x-label>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        Livewire.on('scrollToTop', () => {
            const selectedElement = document.querySelector('.bg-blue-50');
            if (selectedElement) {
                selectedElement.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        });
    </script>
@endpush