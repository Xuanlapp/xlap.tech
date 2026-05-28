<div class="px-6 py-4 bg-slate-200 space-y-3">
    <x-program.program-card programId="{{$programData->id}}"/>
    <div class="text-lg text-gray-500 font-bold">BASE DESIGN of INSERT</div>
    @php
        $firstSubForm = $sub_forms[0] ?? null;
    @endphp
    <a href="{{ route('program.color_form', ['color_group' => $firstSubForm['color_group'], 'form_id' => $firstSubForm['form_id']]) }}"
       target="_blank"
       class="bg-white rounded-lg shadow-md block cursor-pointer hover:bg-slate-100 hover:shadow-lg focus:outline-none">
        <div class="p-6 mb-5 grid grid-cols-3 gap-3">
            <!-- Hiển thị hình ảnh -->
            <div class="col-span-1">
                <img src="{{ asset('images/placements/FormDesignPhotos/1_Base_Front.png') }}" alt="Card Front"
                     class="bg-cover rounded-md mr-4">
            </div>
            <div class="col-span-1">
                <img src="{{ asset('images/placements/FormDesignPhotos/1_Base_Back.png') }}" alt="Card Back"
                     class="bg-cover rounded-md">
            </div>

            <!-- Show data -->
            <div id="form_info" class="col-span-1 flex flex-col gap-3 justify-center h-full p-5">
                @if($firstSubForm)
                    <div class="flex gap-2 items-center">
                        <x-atomics.badge :color="'blue'" :text="'Form'"/>
                        <div class="text-xl text-blue-500">{{ implode(', ', $firstSubForm['forms'] ?? ['N/A']) }}</div>
                    </div>
                    <div class="font-bold text-2xl text-blue-500">
                        {{ $firstSubForm['insert_name'] }}
                    </div>
                    <x-atomics.desc-field>
                        <x-slot name="description">
                            Front
                        </x-slot>
                        <x-slot name="item">
                            {{ $firstSubForm['prepress_color_front'] ?? 'N/A' }}
                        </x-slot>
                    </x-atomics.desc-field>
                    <x-atomics.desc-field>
                        <x-slot name="description">
                            Back
                        </x-slot>
                        <x-slot name="item">
                            {{ $firstSubForm['prepress_color_back'] ?? 'N/A' }}
                        </x-slot>
                    </x-atomics.desc-field>
                @else
                    <p>No sub-form data available</p>
                @endif
            </div>
        </div>
    </a>
    <div class=" bg-slate-200 space-y-3">

    @php
        $remainingSubForms = array_slice($sub_forms, 1);
    @endphp
    @if (empty($remainingSubForms))
    @else
        <div class="px-6 text-lg text-gray-500 font-bold">PARALLELS</div>
        <!-- Vùng cuộn ngang -->
        <div id="scrollContainerLandtour"
             class="relative space-y-12 md:grid md:gap-8 md:space-y-1 overflow-x-auto items-end pb-4 scroll-px-6">
            <div class="flex space-x-5 overflow-x-auto min-w-max scroll-container">
                <div class="flex space-x-5 px-6 overflow-x-auto min-w-max">
                    @foreach ($remainingSubForms as $group)
                        <a href="{{ route('program.color_form', ['color_group' => $group['color_group'], 'form_id' => $group['form_id']]) }}"
                           target="_blank"
                           class="w-64 flex-shrink-0 bg-white p-4 rounded-lg shadow-md my-2 cursor-pointer hover:shadow-lg hover:bg-slate-100 focus:outline-none">
                            <div class="grid grid-cols-2 gap-2 justify-items-center">
                                <img
                                        src="{{ asset('images/placements/FormDesignPhotos/1_Base_Front.png') }}"
                                        alt="Card Front"
                                        class="h-auto rounded-md">
                                <img
                                        src="{{ asset('images/placements/FormDesignPhotos/1_Base_Back.png') }}"
                                        alt="Card Back"
                                        class="h-auto rounded-md">
                            </div>
                            <div class="flex-col justify-center items-center gap-2 mt-3">
                                <div class="flex gap-2">
                                    <div class="mb-2 text-sm flex gap-2 items-center">
                                        <x-atomics.badge :color="'blue'" :text="'Form'"/>
                                        <div class="text-lg text-blue-500">{{ implode(', ', $group['forms']) }}</div>
                                    </div>
                                </div>
                                <div class="text-xl text-blue-500 font-bold">
                                    {{ $group['insert_name'] }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $group['prepress_color_front'] }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @endif

        <style>
            .scrollbar-hidden::-webkit-scrollbar {
                display: none;
            }

            .scrollbar-hidden {
                -ms-overflow-style: none; /* IE và Edge */
                scrollbar-width: none; /* Firefox */
                cursor: grab;
            }
        </style>
        <script>
            document.addEventListener("livewire:load", function () {
                Livewire.on("modal:open", function (modalName) {
                    if (modalName === "modals.program.image-import") {
                        setTimeout(() => {
                            initializeScrolls();
                        }, 300);
                    }
                });
            });

            window.initializeScrolls = function () {
                let scrollContainer = document.getElementById("scrollContainerLandtour");

                if (!scrollContainer) {
                    console.warn("scrollContainerLandtour not found! Waiting...");
                    return;
                }

                console.log("Initializing scroll...");
                scrollContainer.addEventListener("wheel", (evt) => {
                    evt.preventDefault();
                    scrollContainer.scrollLeft += evt.deltaY;
                });
            };
        </script>
        {{-- <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.addEventListener('modal:open', function (event) {
                    if (event.detail.name === 'modals.program.image-import') {
                        initializeScrolls();
                    }
                });
            });
        </script> --}}
    </div>
</div>

