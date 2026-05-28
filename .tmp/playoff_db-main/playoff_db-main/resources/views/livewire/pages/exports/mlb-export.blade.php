<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    <div class='text-center text-4xl text-green-500 mb-5'>⚾️ MLB Export Execution</div>
    <div class="text-center flex-col space-y-3">
        <div class="text-xl text-gray-500">Take csv|xlsx file only</div>
        <div>
            <input type="file" wire:model="file">
        </div>
        <div>
            <x-atomics.button-info wire:click="handleFileUpload">Import File</x-atomics.button-info>
            <div wire:loading.delay>
                Processing...
            </div>
        </div>
    </div>
    @if( $partialShowingData != [])
        <div class="bg-white shadow-sm rounded-md p-3 flex space-x-3 items-center mt-3">
            <div class="text-6xl">
                🪄
            </div>
            <div class="flex-col space-y-2 w-full">
                <div>
                    <input type="checkbox" wire:model="selectAll" class="w-6 h-6 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" /> 
                    <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Check all</label>
                </div>
                <div class="flex justify-between">
                    <div>
                        <x-atomics.button-info wire:click="selectBetween()"><span class="text-md pr-1">↕ </span> Select Between</x-atomics.button-info>
                        @if(count($selectedItems) > 0)
                            <button class="bg-red-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-700 focus:outline-none focus:border-red-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click='$emit("openModal", "modals.mlb.email-report")'><span class="text-md pr-1">✉️ </span> Email Report</button>
                        @else 
                            <x-atomics.button-disabled wire:click="warningMessage('Please select players to report!')"><span class="text-md pr-1">✉️ </span> Email Report</x-atomics.button-disabled>
                        @endif
                    </div>
                    <div class='flex-1 px-5 flex'>
                        <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Filter by Status</label>
                        <select class="select2 form-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" wire:model="filteredBy" placeholder="Show All">
                            <option value="">Show All ({{count($fullShowingData)}})</option>
                            @foreach($sumStatus as $key => $value)
                            <option value="{{$key}}">{{$value['name']}} ({{$value['count']}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-col flex-shrink space-y-2">
                        <div>
                            <input type="checkbox" wire:model="outputTeamNameAbb" class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                            <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-500">Output with full Team Name</label>
                        </div>
                        @if(count($selectedItems) > 0)
                            <x-atomics.button-green wire:click="outputCsv"><span class="text-md pr-1">📄 </span> Output CSV</x-atomics.button-green>
                            @if (session()->has('downloadLink') && session('showDownloadLink', false))
                                <a href="{{ session('downloadLink') }}" target="_blank" class="text-blue-600 underline ml-4">Download CSV</a>
                            @endif
                        @else 
                            <x-atomics.button-disabled wire:click="warningMessage('Please select players to Output to CSV!')"><span class="text-md pr-1">📄 </span> Output CSV</x-atomics.button-disabled>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @foreach ($partialShowingData as $row_key => $row )
        @php
            switch($row['status']) {
                case 0:
                    $color = 'bg-gray-200';
                    $status = 'New';
                    break;
                case 4:
                    $color = 'bg-green-200';
                    $status = 'Approved';
                    break;
                case 1:
                    $color = 'bg-orange-200';
                    $status = 'No Matching';
                    break;
            }
            
            $mlbId = array_key_exists("MlbId", $row)?$row['MlbId']:"";
        @endphp
        <div class="my-3 flex shadow-sm p-5 rounded-md hover:shadow-md space-x-3 w-full {{$color}}">
            <div class="flex-none space-y-3">
                <input type="checkbox" wire:model="selectedItems" value="{{$row['Card #']}}" class="w-5 h-5 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 mr-3"> 
                <div class="text-xl font-bold">{{$row['Card #']}}</div>
            </div>
            <div class="flex-none">
                <img src="https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_639,q_auto:best/v1/people/{{ $mlbId }}/headshot/67/current" alt="" class="object-cover h-32">
            </div>
            <div class="flex-none">
                <div class="text-2xl font-bold">{{$row['Player']}}</div>
                <div class=""><span class="text-gray-500">Player ID:</span> {{$row['PlayerID']}}</div>
                <div class=""><span class="text-gray-500">MLB ID:</span> {{$mlbId}}</div>
                <div><span class="text-gray-500">Status in DB: </span>{{$status}} </div>
                <div><span class="text-gray-500">Team:</span> {{$row['Team']}}</div>
                <div><span class="text-gray-500">Status:</span> {{$row['Status']}}</div>
            </div>
            <div class="pl-5 flex-1 overflow-x-auto">
            @if( isset($row['statTitle']))
                @if($row['statTitle'] !== null)
                    <table class="table-auto w-full text-center">
                        <thead>
                            <tr>
                                <th class=""></th>
                                <th class=""></th>
                            @foreach ($row['statTitle'] as $title_stat)
                                <th class="">{{$title_stat}}</th>
                            @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($row['statOneYear'] as $year)
                                <tr class="">
                                @foreach($year as $item)
                                    <td class=""> {{$item}}</td>
                                @endforeach
                                </tr>
                            @endforeach
                            <tr>
                                <td class="font-bold">Career</td>
                                <td></td>
                            @foreach ($row['career'] as $career_stat)
                                <td class="">{{$career_stat}}</td>
                            @endforeach
                            </tr>
                        </tbody>
                    </table>
                    @endif
                @else
                    <div class="text-4xl text-center text-gray-400">Stat is not available</div>
                @endif
            </div>
        </div>
        @endforeach
    @else
        <x-molecules.export-instruction/>
    @endif
</div>