<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5 py-5">
    <div class="flex bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 ">
        @foreach ($locations as $location)
            <div class="relative flex flex-col md:flex-row w-full my-6 bg-white shadow-sm border border-slate-200 rounded-lg ">
                <div class="relative p-1.5 md:w-1/5 shrink-0 overflow-hidden">
                    <img
                            src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=1471&amp;q=80"
                            alt="card-image"
                            class="h-full w-full rounded-md md:rounded-lg object-cover"
                    />
                </div>
                <div class="p-6">
                    @foreach ($location->departments as $department)
                        {{--                        <h4 class="text-lg font-semibold text-green-700">🏢 {{ $department->department_name }}</h4>--}}
                        @if ($department->employees->count() > 0)
                            @foreach ($department->employees as $employee)
                                <div class="flex">
                                    <h4 class="mb-2 text-slate-800 text-xl font-semibold">
                                        {{$employee->name}}
                                    </h4>
                                    <div class="mb-4 rounded-full bg-teal-600 py-0.5 px-2.5 border border-transparent text-xs text-white transition-all text-center shadow-sm w-20 mt-1 ml-2">
                                        {{ $employee->position }}
                                    </div>

                                </div>
                                <div class="flex">
                                    <x-label for="department" :value="__('ContactDepartment:')"/>
                                    <p class="text-gray-500 ml-4">{{ $department->department_name }}</p>
                                </div>
                                <div>
                                    <div class="flex">
                                        <x-label for="phone" :value="__('Phone: ')"/>
                                        <p class="text-gray-500 ml-4">{{ $employee->phone }}</p>
                                    </div>
                                    <div class="flex">
                                        <x-label for="email" :value="__('Email: ')"/>
                                        <p class="text-gray-500 ml-4">{{ $employee->email }}</p>
                                    </div>
                                    <div class="flex">
                                        <x-label for="location_name" :value="__('ContactLocation Name')"/>
                                        <x-label class=" ml-1 mr-1" for="-" :value="__('-')"/>
                                        <x-label for="address" :value="__('Address')"/>
                                    </div>
                                    <div class="flex">
                                        <p class="text-gray-500">{{ $location->location_name }}</p>
                                        <x-label class=" ml-1 mr-1" for="-" :value="__('-')"/>

                                        <p class="text-gray-500">{{ $location->address }}</p>
                                    </div>
                                </div>
                                @role($edit['permission'])
                                <div>
                                    <button class="rounded-full border border-slate-300 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
                                            href=https://www.facebook.com/xuanlaap2502"">
                                        <x-label

                                                for="edit" :value="__('Edit')"/>
                                    </button>

                                </div>
                                @endrole
                            @endforeach

                        @else
                            <p class="text-gray-500 ml-4">⛔ Không có nhân viên.</p>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

</div>