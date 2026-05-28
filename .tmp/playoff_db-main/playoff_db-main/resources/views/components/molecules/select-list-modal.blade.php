<div class="mt-2">
    <div x-data="{ open: false, search: '', selectedValue: @entangle($attributes->wire('model')), options: [
     { value: 'None', label: 'None' },
            @foreach($data as $key => $value)
                { value: '{{ $value }}', label: '{{ $key }}' },
            @endforeach
            ],get filteredOptions() {
            return this.options.filter(option =>
                option.label.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        select(value) {
            this.selectedValue = value;
            this.open = false;
        }
    }"
         class="relative">
        <button
                @click="open = !open"
                type="button"
                class="block w-full bg-white border border-gray-300 rounded-md py-2 px-3 text-left shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            <span x-text="selectedValue ? options.find(o => String(o.value) === String(selectedValue))?.label : 'Select'"></span>

            <span class=" absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"/>
            </svg>
            </span>
        </button>
        <div
                x-show="open"
                @click.away="open = false"
                class="absolute z-50 mt-1 w-full bg-white shadow-lg  rounded-md py-1 text-base ring-1 ring-black ring-opacity-5  focus:outline-none sm:text-sm"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">

            <div class="sticky top-0 z-10 bg-white px-3 pt-3">
                <input
                        x-model="search"
                        type="text"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Please Select"
                        @click.stop
                />
            </div>
            <div class=" max-h-40  overflow-y-auto z-50 ">
                <template x-for="option in filteredOptions" :key="option.value">
                    <div
                            @click="select(option.value)"
                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50"
                            :class="{ 'bg-blue-100': selectedValue === option.value }"
                    >
                        <span x-text="option.label" class="block truncate"></span>

                        <span
                                class="absolute inset-y-0 right-0 flex items-center pr-4"
                                x-show="selectedValue === option.value"
                        >
                <svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clip-rule="evenodd"/>
                </svg>
            </span>
                    </div>
                </template>
            </div>
            <div x-show="filteredOptions.length === 0" class="py-2 px-3 text-gray-500 text-sm">
                No Result
            </div>
        </div>
    </div>
</div>