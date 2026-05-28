<div class="p-6">
    <h2 class="text-2xl font-bold mb-4 text-center">Logo Check Results</h2>
    
    <div class="mb-4 bg-blue-50 p-4 rounded-md">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg">{{ $versionData['nba_team']['team_name'] }}</h3>
                <p class="text-sm text-gray-600">Year: {{ $versionData['begin'] }} - {{ $versionData['end'] == 3000 ? 'Present' : $versionData['end'] }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm"><span class="font-semibold">Primary TC:</span> {{ $versionData['pri_tc'] }}</p>
                <p class="text-sm"><span class="font-semibold">Secondary TC:</span> {{ $versionData['sec_tc'] }}</p>
            </div>
        </div>
    </div>
    
    @if($isLoading)
        <div class="flex justify-center items-center p-8">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-lg">Loading logo data...</span>
        </div>
    @else
        @if(isset($logoCheckResults) && count($logoCheckResults) > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Logo Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Color Type</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">File Path</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($logoCheckResults as $type => $colorData)
                            @foreach($colorData as $colorType => $data)
                                <tr class="{{ $loop->parent->even ? 'bg-gray-50' : '' }}">
                                    @if($loop->first)
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6" rowspan="{{ count($colorData) }}">
                                            {{ $type }}
                                            @if(isset($logoTypes[$type]['description']))
                                                <div class="text-xs text-gray-500">{{ $logoTypes[$type]['description'] }}</div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <span class="px-2 py-1 text-xs font-medium rounded {{ $colorType == 'primary' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ ucfirst($colorType) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500 break-all">
                                        @if(isset($data['web_path']) && !empty($data['web_path']))
                                            {{ $data['web_path'] }}
                                        @else
                                            <span class="text-red-500">Path not available</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if($data['exists'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Exists
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                Missing
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-sm text-gray-500">
                <p>Total logo types: {{ count($logoCheckResults) }}</p>
                <p>Total files to check: {{ collect($logoCheckResults)->flatten(1)->count() }}</p>
                <p>Existing files: {{ collect($logoCheckResults)->flatten(1)->where('exists', true)->count() }}</p>
                <p>Missing files: {{ collect($logoCheckResults)->flatten(1)->where('exists', false)->count() }}</p>
            </div>
        @else
            <div class="text-center p-8 bg-gray-50 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-500 text-lg">No logo data found</p>
            </div>
        @endif
    @endif
</div>