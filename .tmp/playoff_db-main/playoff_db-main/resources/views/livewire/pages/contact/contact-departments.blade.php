<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    @role('admin|conatct editor')
    <div class="rounded-md space-y-5 mt-5 text-right ">
        <x-button wire:click="$emit('openModal', 'modals.contact.departments.contact-department-add')">Add
            Departments
        </x-button>
    </div>
    @endrole
    <div class="bg-white shadow-sm rounded-md space-y-5 mt-5">
        <table class="table-auto w-full text-left text-gray-500">
            <thead class="bg-slate-200">
            <tr class="h-16">
                <th class="px-4 py-2">Departments Name</th>
                <th class="px-4 py-2">Location Name</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($departments as $department)
                <tr class="h-16 even:bg-slate-50 hover:bg-slate-100">
                    <td class="px-4">{{ $department->department_name }}</td>
                    <td class="px-4">{{ $department->location->location_name }}</td>

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
                                       wire:click='$emit("openModal", "modals.contact.departments.contact-department-detail", {{ json_encode(['departments_id' => $department->id]) }})'
                                    >
                                        <x-icons.spinner wire:loading class="fill-white h-5 mr-1" size="6"/>
                                        🔍 Edit
                                    </a>
                                    <a class="hover:cursor-pointer block px-4 py-2 text-lg leading-5 text-red-600 hover:bg-red-100 focus:outline-none focus:bg-red-100 transition"
                                       onclick="confirm('Are you sure you want to delete this departments?') || event.stopImmediatePropagation()"
                                       wire:click="deletedepartments({{ $department->id }})"
                                    >
                                        🗑️ Delete
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
</div>
