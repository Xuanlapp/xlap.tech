<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-5">
    <!-- Export CSV Button -->
    <div class="py-3 text-center text-5xl text-slate-400">Program Parallel List View</div>
    <x-program.program-card programId="{{$programs->id}}"/>
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 mt-3 items-center h-24">
        <div class="flex items-center space-x-4">
            <label class="flex items-center text-slate-400">
                <input type="radio" name="sortOption" value="form" wire:model="sortOption" class="mr-2">
                Sort by Form
            </label>
            <label class="flex items-center text-slate-400">
                <input type="radio" name="sortOption" value="insert_name" wire:model="sortOption" class="mr-2">
                Order by Insert Name
            </label>
        </div>

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
                       type="text" wire:model="search" placeholder="Search Insert Name"/>
            </div>
        </div>
        {{-- first column filter start --}}

    </div>

    @if ($sortOption === 'form')
        <!-- Sticky Div for Form Numbers -->
        <div id="formNumbers"
             class="sticky top-0 backdrop-blur-md bg-white/30 shadow-md z-10 pt-2 p-4 flex gap-2 overflow-auto rounded-md">
            @foreach ($ProgramsSubForm->unique('form')  as $program)
                @if (!str_contains($program->form, '-'))
                    <div class="w-12 h-12">
                        <button class="bg-blue-300 text-white font-bold p-2 rounded-full hover:bg-blue-700 w-10 h-10 text-center"
                                onclick="scrollToElementWithOffset('form-row-{{ $program->id }}', -document.getElementById('formNumbers').offsetHeight);">
                            F{{ $program->form }}
                        </button>
                    </div>
                @endif
            @endforeach
        </div>
    @endif


    <div class="bg-white shadow-sm rounded-md space-y-5 mt-5
    ">
        <table class="table-auto w-full text-left text-gray-500">
            <thead class="bg-slate-200">
            <tr class="h-16">
                <th class="px-4 py-2">Form</th>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Insert Name</th>
                <th class="px-4 py-2">Config</th>
                <th class="px-4 py-2">Cards</th>
                <th class="px-4 py-2"></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($ProgramsSubForm as $SubForm)
                <tr id="form-row-{{ $SubForm->id }}"
                    class=" h12 even:bg-slate-50 hover:bg-slate-100 border rounded-md cursor-pointer "
                    onclick="event.stopPropagation()"
                    wire:click='$emit("openModal", "modals.program.change-color" , {{ json_encode(["SubForm_Id" => $SubForm->id ,"sortOption" => $sortOption]) }})'>
                    <td class="px-4 py-2  @if (str_contains($SubForm->form, '-')) transform translate-x-4 @endif">{{ $SubForm->form }}</td>
                    <td class="px-4 py-2">{{ $SubForm->id }}</td>
                    <td class="px-4 py-2">{{ $SubForm->insert_name }}</td>
                    <td class="px-4 py-2">{{ $SubForm->config }}</td>
                    <td class="px-4 py-2">{{ $SubForm->cards }}</td>
                    <td onclick="event.stopPropagation()">

                        @role(' admin|auditor')
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
                                       wire:click='$emit("openModal","modals.program.sub-form-detail", {{ json_encode(["SubForm_Id" => $SubForm->id ]) }})'>
                                        🔍 Detail
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