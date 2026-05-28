<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
    <!-- Export CSV Button -->

    <div class="p-6 bg-white rounded-lg shadow-md mb-5">
        <button wire:click="exportCsv({{ $programs->id }})"
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
            <x-icons.spinner wire:loading wire:target="exportCsv" class="fill-white h-5 mr-1" size="6"/>
            Export CSV
        </button>
        <button wire:click="GroupSubForm({{ $programs->id }} )"
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded"
                onclick="confirm('Are you sure you want to change the group form?') || event.stopImmediatePropagation()">
            <x-icons.spinner wire:loading wire:target="GroupSubForm" class="fill-white h-5 mr-1" size="6"/>
            Group Sub Form
        </button>
        <button wire:click="exportAllSubForms({{$programs->id}})"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <x-icons.spinner wire:loading wire:target="exportAllSubForms" class="fill-white h-5 mr-1" size="6"/>
            Export All SubForms
        </button>
        <button wire:click="processPrepressData({{ $programs->id }})"
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            <x-icons.spinner wire:loading wire:target="processPrepressData" class="fill-white h-5 mr-1" size="6"/>
            Process Prepress Data
        </button>
    </div>

    <!-- Other Action Buttons -->
    {{-- <div class="p-6 bg-white rounded-lg shadow-md mb-5">
        <button class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
            Edit-Photo-Color
        </button>
        <button class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
            Test Rip-
        </button>
        <button class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
            Edit-Photo-Color
        </button>
    </div> --}}
    <x-program.program-card programId="{{$programs->id}}"/>
    <div id="formNumbers"
         class="sticky top-0 backdrop-blur-md bg-white/30 shadow-md z-10 pt-2 p-4 flex gap-2 overflow-auto rounded-md">
        @foreach ($mainPrograms->unique('form') as $program)
            <!-- 使用 unique 方法去重 -->
            <div class="w-12 h-12">
                <button class="bg-blue-300 text-white font-bold p-2 rounded-full hover:bg-blue-700 w-10 h-10 text-center"
                        onclick="scrollToElementWithOffset('form-row-{{ $program->id }}', -document.getElementById('formNumbers').offsetHeight);">
                    F{{ $program->form }}
                </button>
            </div>
        @endforeach
    </div>

    <!-- Table showing main programs with Dropdown -->
    <div class=" p-3 bg-white shadow-md rounded-md space-y-5 mt-3">
        @foreach ($mainPrograms as $program)
            {{-- this ID is mandetory to have make the shortcut form work --}}
            <div id="form-row-{{ $program->id }}"
                 class="border p-4 rounded-md bg-slate-50 shadow-sm hover:bg-slate-100 hover:shadow-md space-y-3 relative cursor-pointer"
                 wire:click='$emit("openModal", "modals.program.group-sub-form", {{ json_encode(["program_id" => $program->program_id ,"form"=>$program->form,"form_insert_name"=>$program->insert_name,"form_id"=>$program->id]) }})'
                 onclick="event.stopPropagation()">
                <div class="float-left absolute flex items-center space-x-2" style="top: -15px; left: -5px;">
                    <div class="w-12 h-12">
                        <div class="text-center bg-blue-400 shadow-md rounded-full p-3 text-white">
                            {{ $program->form }}
                        </div>
                    </div>
                    <div>
                        <div class="text-center shadow-md bg-gray-400 rounded-sm py-1 px-3 text-white text-xs">
                            {{ $program->id }}
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="grid grid-cols-12 items-top pt-0">
                    <!-- Insert Name -->
                    <x-atomics.desc-field class="col-span-4" item-class="font-bold text-gray-600">
                        <x-slot name="description">
                            Insert Name
                        </x-slot>
                        <x-slot name="item">
                            {{ $program->insert_name }}
                        </x-slot>
                        <span class="text-sm text-blue-400">{{ $program->insert_short_name }}</span>
                    </x-atomics.desc-field>

                    <!-- Config -->
                    <x-atomics.desc-field class="col-span-4">
                        <x-slot name="description">
                            Config
                        </x-slot>
                        <x-slot name="item">
                            {{ $program->config }}
                        </x-slot>
                    </x-atomics.desc-field>

                    <!-- Cards -->
                    <x-atomics.desc-field class="col-span-4">
                        <x-slot name="description">
                            Cards
                        </x-slot>
                        <x-slot name="item">
                            {{ $program->cards }}
                        </x-slot>
                    </x-atomics.desc-field>
                </div>

                <div class="grid grid-cols-12 items-top pt-0">
                    <div class="col-span-2  ">
                        <div>
                            <x-label class="text-white text-center bg-green-400 " name="pdt">
                                PDT
                            </x-label>
                        </div>
                        <div>
                            <x-label class="text-black text-center " name="playlist">
                                Players List
                            </x-label>
                        </div>
                        <div class="flex justify-between mx-2">
                            <x-label class="text-black text-center col-span-1 " name="Duedate">
                                Due Date
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="Compieted">
                                Completed
                            </x-label>
                        </div>
                    </div>

                    <div class="col-span-2  ">
                        <div>
                            <x-label class="text-white text-center bg-yellow-400" name="editorial">
                                Editorial
                            </x-label>
                        </div>
                        <div>
                            <x-label class="text-black text-center " name="copy">
                                Copy/Calayouts/Stats
                            </x-label>
                        </div>
                        <div class="flex justify-between mx-2">
                            <x-label class="text-black text-center col-span-1 " name="Duedate">
                                Due Date
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="Compieted">
                                Completed
                            </x-label>
                        </div>

                        <div class="flex justify-between mx-2">
                            <!-- Dynamic Due Date -->
                            <x-label class="text-black text-center col-span-1" name="Duedate">
                                {{ $program->edit["due"] ?? 'N/A' }}
                            </x-label>

                            <!-- Dynamic Completed -->
                            <x-label class="text-black text-center col-span-1" name="Compieted">
                                {{ $program->edit['done'] ?? ' ' }}
                            </x-label>
                        </div>
                    </div>
                    {{--                    @php  dd($program->photo['due']);--}}
                    {{--                    @endphp--}}
                    <div class="col-span-2  ">
                        <x-label class="text-white text-center bg-orange-400" name="photo">
                            Photo
                        </x-label>
                        <div class="h-5"></div>
                        <div class="flex justify-between mx-2">
                            <x-label class="text-black text-center col-span-1 " name="Duedate">
                                Due Date
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="Compieted">
                                Completed
                            </x-label>
                        </div>

                        <div class="flex justify-between mx-2">
                            <!-- Dynamic Due Date -->
                            <x-label class="text-black text-center col-span-1" name="Duedatess">
                                {{ $program->photo['due'] ?? 'N/A' }}
                            </x-label>

                            <!-- Dynamic Completed -->
                            <x-label class="text-black text-center col-span-1" name="Completedss">
                                {{ $program->photo['note'] ?? ' ' }}
                            </x-label>
                        </div>
                    </div>
                    <div class="col-span-3 ">
                        <x-label class="text-white text-center bg-violet-400 " name="precrop">
                            PreCrop
                        </x-label>
                        <div class="h-5"></div>
                        <div class="flex justify-between mx-2">
                            <x-label class="text-black text-center col-span-1 " name="Duedate">
                                Due Date
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="status">
                                Status
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="approved">
                                Approved
                            </x-label>
                        </div>
                    </div>
                    <div class="col-span-3 ">
                        <x-label class="text-white text-center bg-blue-400 " name="desingpacktestrip">
                            Desing Pack/TestRip
                        </x-label>
                        <div class="h-5"></div>
                        <div class="flex justify-between mx-2">
                            <x-label class="text-black text-center col-span-1 " name="Duedate">
                                Due Date
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="status">
                                Status
                            </x-label>
                            <x-label class="text-black text-center col-span-1 " name="approved">
                                Approved
                            </x-label>
                        </div>
                    </div>
                    {{--                    <div class="col-span-3 ">--}}
                    {{--                        <x-label class="text-white text-center bg-purple-400 " name="build">--}}
                    {{--                            Build--}}
                    {{--                        </x-label>--}}
                    {{--                    </div>--}}
                </div>


                <div class="absolute right-0 w-12 h-12" style="top: -5px; right: 12px;"
                     onclick="event.stopPropagation()">
                    @role('admin|auditor')
                    <div class="px-3">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="p-2 text-xl text-gray-400 hover:text-blue-500 hover:bg-slate-200 rounded-md">
                                    •••
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    More Actions
                                </div>
                                <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                   wire:click='$emit("openModal", "modals.program.form-detail", {{ json_encode(['program_form'=> $program->id]) }})'>
                                    🔍 Detail
                                </a>
                                <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                   wire:click='$emit("openModal", "modals.program.image-import", {{ json_encode(['program_id'=> $program->program_id,'Form_id'=>$program->id,'Form'=>$program->form]) }})'>
                                    🖼 Image
                                </a>
                                <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                                   onclick="confirm('Are you sure you want to change the group F{{$program->form}} ?') || event.stopImmediatePropagation()"
                                   wire:click="SpitForms({{ $program->program_id }}, '{{ $program->form }}')">
                                    ↩︎ Spit Forms
                                </a>
                                {{--                                <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"--}}
                                {{--                                   wire:click='uploadImage({{$program->program_id}},{{$program->id}},{{$program->form}})'>--}}
                                {{--                                    🖼 Image--}}
                                {{--                                </a>--}}
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endrole
                </div>
            </div>
        @endforeach
    </div>
    <script>
        window.addEventListener('refresh-page', event => {
            window.location.reload(false);
        })
    
        function scrollToElementWithOffset(elementId, offset) {
            const element = document.getElementById(elementId);
            if (element) {
                const elementPosition = element.getBoundingClientRect().top + window.scrollY; // 计算目标元素的绝对位置
                const offsetPosition = elementPosition + offset; // 应用偏移量
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        }
    
        document.addEventListener('DOMContentLoaded', function () {
            const programContainer = document.querySelector('.main-container');
    
            // Lắng nghe sự kiện scroll
            window.addEventListener('scroll', () => {
                const programElements = document.querySelectorAll('.program-element');
    
                programElements.forEach(element => {
                    const formValue = element.dataset.form.replace('F', ''); // Loại bỏ chữ 'F' để lấy số
    
                    if (formValue === "2") {
                        // Nếu formValue = 2, di chuyển lên đầu
                        if (!programContainer.firstChild.isEqualNode(element)) {
                            programContainer.prepend(element);
                        }
                    }
                });
            });
        });
    
    </script>
</div>