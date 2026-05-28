<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    <x-program.program-card programId="{{$programData->id}}"/>

    <div class="py-2 text-center text-5xl text-slate-400">Insert Parallel Design View</div>
    <div class="p-6 bg-white rounded-lg shadow-md mb-5">
        <div class="flex justify-between mt-4">
            <!-- Previous Button (Left) -->
            <a href="{{ $colorGroupIndex > 0 ? route('program.color_form', ['color_group' => $sub_forms[$colorGroupIndex - 1], 'form_id' => $programFormsData->id]) : '#' }}"
               class="btn btn-primary
              {{ $colorGroupIndex > 0 ? 'bg-blue-500 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed' }}
              text-white py-2 px-4 rounded-md"
                    {{ $colorGroupIndex > 0 ? '' : 'aria-disabled=true' }}>
                Previous
            </a>

            <!-- Next Button (Right) -->
            <a href="{{ $colorGroupIndex < count($sub_forms) - 1 ? route('program.color_form', ['color_group' => $sub_forms[$colorGroupIndex + 1], 'form_id' => $programFormsData->id]) : '#' }}"
               class="btn btn-primary
              {{ $colorGroupIndex < count($sub_forms) - 1 ? 'bg-blue-500 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed' }}
              text-white py-2 px-4 rounded-md"
                    {{ $colorGroupIndex < count($sub_forms) - 1 ? '' : 'aria-disabled=true' }}>
                Next
            </a>
        </div>
        <div>
            <!-- Insert Name (Base) -->
            <div class="flex justify-center items-center mb-4">
                <span class="text-lg font-semibold text-gray-800">{{$programData->code}}_{{ $formgroup[0]['insert_name'] ?? 'Base' }}</span>
            </div>

            <!-- Nội dung chính -->
            <div class="grid grid-cols-12 gap-3 items-center">

                <!-- Form Color Front -->
                <div class="col-span-3 flex justify-center items-center mb-60">
                    <div class="bg-blue-100 text-center text-sm font-semibold px-4 py-2 rounded-md shadow">
                        <strong>Form Color Front:</strong><br>
                        {{ $formgroup[0]['prepress_color_front'] ?? 'N/A' }}
                    </div>
                </div>

                <!-- Ảnh Front và Back -->
                <div class="col-span-6 flex justify-center">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/placements/FormDesignPhotos/1_Base_Front.png') }}" alt="Card Front"
                             class="w-1/2 h-auto rounded-md cursor-pointer"
                             wire:click='$emit("openModal", "modals.program.image-modal", { url: "{{ asset('images/placements/FormDesignPhotos/1_Base_Front.png') }}" })'
                             onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
                        <img src="{{ asset('images/placements/FormDesignPhotos/1_Base_Back.png') }}" alt="Card Back"
                             class="w-1/2 h-auto rounded-md cursor-pointer"
                             wire:click='$emit("openModal", "modals.program.image-modal",{ url: "{{ asset('images/placements/FormDesignPhotos/1_Base_Back.png') }}" })'
                             onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';"
                        >
                    </div>
                </div>
                <div class="col-span-3 flex justify-center items-center mb-60">
                    <div class="bg-blue-100 text-center text-sm font-semibold px-4 py-2 rounded-md shadow">
                        <strong>Form Color Back:</strong><br>
                        {{ $formgroup[0]['prepress_color_back'] ?? 'N/A' }}
                    </div>
                </div>

            </div>
        </div>

        @php
            // Decode JSON strings to arrays
            $prepressColorFrontJson = isset($formgroup[0]['prepress_color_front_json'])
                ? json_decode($formgroup[0]['prepress_color_front_json'], true)
                : [];

            $prepressColorBackJson = isset($formgroup[0]['prepress_color_back_json'])
                ? json_decode($formgroup[0]['prepress_color_back_json'], true)
                : [];

            // Replace values in prepressColorFrontJson
            $prepressColorFrontJson = array_map(function ($item) {
                if ($item === 'EE') return 'ELECTRA_ETCH';
                if ($item === 'WOPX2') return 'WHT_OP';
                return $item;
            }, $prepressColorFrontJson);

            // Replace values in prepressColorBackJson
            $prepressColorBackJson = array_map(function ($item) {
                if ($item === 'EE') return 'ELECTRA_ETCH';
                if ($item === 'WOPX2') return 'WHT_OP';
                return $item;
            }, $prepressColorBackJson);
        @endphp

        <div class="mt-4">
            <div class="grid grid-cols-12 gap-3">
                <!-- Front Images Section -->
                <div class="col-span-6">
                    <h3 class="text-center font-bold mb-2">Front Images</h3>
                    <div class="grid grid-cols-4 gap-4">
                        @if (is_array($prepressColorFrontJson))
                            @foreach ($prepressColorFrontJson as $index => $detail)
                                @if ($detail !== '4/C')
                                    {{--                                    <div class="flex justify-center">--}}
                                    <div class="flex flex-col items-center">
                                        <img src="{{ asset('images/201732/503559-F1/' . $detail . '.png') }}"
                                             alt="{{ $detail }}"
                                             class="w-3/4 h-auto rounded-md shadow-md cursor-pointer"
                                             wire:click='$emit("openModal", "modals.program.image-modal",{ url: "{{ asset('images/201732/503559-F1/' . $detail . '.png') }}" })'
                                             onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
                                        <x-label>{{ $detail }}</x-label>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Back Images Section -->
                <div class="col-span-6">
                    <h3 class="text-center font-bold mb-2">Back Images</h3>
                    <div class="grid grid-cols-4 gap-4">
                        @if (is_array($prepressColorBackJson))
                            @foreach ($prepressColorBackJson as $index => $details)
                                @if ($details !== '4/C')
                                    {{--                                    <div class="flex justify-center">--}}
                                    <div class="flex flex-col items-center">
                                        @if ($details === 'SEQ')
                                            <img src="{{ asset('images/201732/503559-F1/' . $details . '.png') }}"
                                                 alt="{{ $details }}"
                                                 class="w-3/4 h-auto rounded-md shadow-md cursor-pointer"
                                                 wire:click='$emit("openModal", "modals.program.image-modal",{ url: "{{ asset('images/201732/503559-F1/' . $details . '.png') }}" })'
                                                 onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
                                            <x-label>{{ $detail }}</x-label>
                                        @else
                                            <img src="{{ asset('images/201732/503559-F1/' . $details . '_b' . '.png') }}"
                                                 alt="{{ $details }}"
                                                 class="w-3/4 h-auto rounded-md shadow-md cursor-pointer"
                                                 wire:click='$emit("openModal", "modals.program.image-modal",{ url: "{{ asset('images/201732/503559-F1/' . $details . '_b' . '.png') }}" })'
                                                 onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
                                            <x-label>{{ $detail }}</x-label>

                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>


    <div class="p-6 bg-white rounded-lg shadow-md mb-5">
        <div class="mt-5">
            @if($groupform)
                <table class="table-auto w-full border-collapse">
                    <thead>
                    <tr>
                        <th class="border px-4 py-2">Form #</th>
                        <th class="border px-4 py-2">Insert Name</th>
                        <th class="border px-4 py-2">SUBSTRATE</th>
                        <th class="border px-4 py-2">Foil</th>
                        <th class="border px-4 py-2">PMS</th>
                        <th class="border px-4 py-2">Prepress Colors Front</th>
                        <th class="border px-4 py-2">Prepress Colors Back</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupform as $item)
                        <tr>
                            <td class="border px-4 py-2">{{ $item['forms'] }}</td>
                            <td class="border px-4 py-2">{{ $item['insert_name'] }}</td>
                            <td class="border px-4 py-2">{{ $item['substrate'] }}</td>
                            <td class="border px-4 py-2">{{ $item['foil'] }}</td>
                            <td class="border px-4 py-2">{{ $item['pms'] }}</td>
                            <td class="border px-4 py-2">{{ $item['prepress_color_front'] }}</td>
                            <td class="border px-4 py-2">{{ $item['prepress_color_back'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p>No data available in the color group.</p>
            @endif

        </div>


    </div>
</div>