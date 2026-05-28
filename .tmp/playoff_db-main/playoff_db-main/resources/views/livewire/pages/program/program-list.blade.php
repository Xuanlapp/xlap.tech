<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    {{-- Upload buttons --}}
    <div class="bg-white p-4 rounded-md shadow-sm mt-3">
        <div class="my-4">
            <x-label>Import Excel File</x-label>
            <div class=" mt-2">
                <x-button onclick="Livewire.emit('openModal', 'modals.program.import-program-excel')"
                          class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4  rounded">
                    Upload Programs Excel File
                </x-button>
                <x-button onclick="Livewire.emit('openModal', 'modals.program.import-doudate-excel')"
                          class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4  rounded">
                    Upload Due Date Excel File
                </x-button>
            </div>
        </div>
    </div>

    {{-- Filter Sport Groups --}}
    <div class="bg-white p-4 rounded-md shadow-sm mt-3">
        <div class="flex gap-2">
            @foreach ($spList as $sp)
                <button class="shadow-sm bg-[{{ $this->getColorForSP($sp) }}] hover:bg-[{{ $this->getColorForSP($sp) }}]/75 hover:shadow-md flex items-center px-4 py-2 text-white font-bold rounded
                @if($this->filter == $sp)
                    border-2 border-indigo-400
                @endif
                " wire:click="filterBySP('{{ $sp }}')">
                    <span>
                        <x-dynamic-component :component="'icons.' . $sp" class="fill-white h-5 mr-1"/>
                    </span>
                    <span>{{ $sp }}</span>
                </button>
            @endforeach
            <button class="px-4 py-2 bg-gray-500 hover:bg-gray-400 text-white rounded" wire:click="clearFilter">
                Clear Filter
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-500 text-white p-4 rounded-md mb-4">
            {{ session('message') }}
        </div>
    @endif


    {{-- filter start --}}
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 mt-3">
        {{-- first column filter start --}}
        <div class="max-w-md mx-auto space-y-3 w-96">
            <label for="default-search"
                   class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="search" id="default-search"
                       class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 "
                       type="text" wire:model="search" placeholder="Type Code # / Collection here"/>
            </div>
        </div>
        {{-- first column filter end --}}
    </div>

    <div class="bg-white shadow-sm rounded-md space-y-5 mt-5">
        <table class="table-auto w-full text-left text-gray-500">
            <thead class="bg-slate-200">
            <tr class="h-16">
                <th class="px-4 py-2">Sport</th>
                <th class="px-4 py-2">Code</th>
                <th class="px-4 py-2">Collection</th>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Year</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($programs as $program)
                <tr class="h-16 even:bg-slate-50 hover:bg-slate-100">
                    <td class="px-4 w-24">
                        <div class="rounded-md p-2 w-24 text-white font-bold flex items-center space-x-2 bg-[{{ $program->getSportHexColor() }}]">
                            <span>
                                <x-dynamic-component :component="'icons.' . $program->sp" class="fill-white h-5 mr-1"/>
                            </span>
                            <span>{{ $program->sp }}</span>
                        </div>
                    </td>
                    <td class="px-4">{{ $program->code }}</td>
                    <td class="flex gap-2 items-center">
                        <div class="flex-none flex items-center h-16">
                            <img src="{{ asset($program->programImage()) }}"
                                 alt="Card Front"
                                 id="programImage-{{ $program->id }}"
                                 class="rounded-md h-10 w-14 object-contain "
                                 onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
                        </div>
                        <div>
                            <a href="{{ route('program.forms', ['programId' => $program->id]) }}" target="_blank"
                               class="flex-1 cursor-pointer hover:text-blue-800 hover:underline text-blue-500 text-xl font-bold">
                                {{ $program->collection }}
                            </a>
                        </div>
                    </td>
                    <td class="px-4">{{ $program->id }}</td>
                    <td class="px-4">{{ $program->year }}</td>
                    <td>
                        @role('admin|auditor')
                        <div class="px-3">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="text-xl text-gray-400 hover:text-blue-500 hover:bg-slate-200 rounded-sm">
                                        •••
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        More Actions
                                    </div>
                                    <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                       wire:click='$emit("openModal", "modals.program.program-detail", {{ json_encode(['program' => $program->id]) }})'>
                                        <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>

                                        🔍 Detail
                                    </a>
                                    <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-red-600 hover:bg-red-100 focus:outline-none focus:bg-red-100 transition"
                                       onclick="confirm('Are you sure you want to delete this program?') || event.stopImmediatePropagation()"
                                       wire:click="deleteProgram({{ $program->id }})"
                                    >
                                        🗑️ Delete
                                    </a>
                                    <a class="flex hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                       href="{{ route('program.sub.forms', ['programId' => $program->id]) }}"
                                       target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20" height="20 "
                                             class="mr-2"
                                             viewBox="0 0 32 32">
                                            <path d="M 23.900391 3.9726562 C 22.853426 3.9726562 21.805365 4.3801809 20.992188 5.1933594 L 5.1796875 21.007812 L 3.7246094 28.275391 L 10.992188 26.820312 L 11.207031 26.607422 L 26.806641 11.007812 C 28.432998 9.381456 28.432998 6.8197164 26.806641 5.1933594 C 25.993462 4.3801809 24.947355 3.9726563 23.900391 3.9726562 z M 23.900391 5.8769531 C 24.403426 5.8769531 24.905757 6.1206004 25.392578 6.6074219 C 26.366221 7.5810649 26.366221 8.620107 25.392578 9.59375 L 24.699219 10.285156 L 21.714844 7.3007812 L 22.40625 6.6074219 C 22.893072 6.1206004 23.397355 5.8769531 23.900391 5.8769531 z M 20.300781 8.7148438 L 23.285156 11.699219 L 11.175781 23.810547 C 10.519916 22.5187 9.4812999 21.480084 8.1894531 20.824219 L 20.300781 8.7148438 z M 6.9042969 22.576172 C 8.0686534 23.064699 8.9374718 23.931222 9.4257812 25.095703 L 6.2753906 25.726562 L 6.9042969 22.576172 z"></path>
                                        </svg>
                                        SUB FORM
                                    </a>
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
    <div class="mt-4">
        {{ $programs->appends(['filter' => $filter, 'search' => $search])->links() }}
    </div>
    <script>
        window.addEventListener('refresh-page', event => {
            window.location.reload(false);
        })

    </script>
</div>
