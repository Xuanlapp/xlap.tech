<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Role Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Role Details: {{ $role->name }}</h3>
                    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                        Back
                    </a>
                </div>

                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-2">Role Information</h4>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <p><strong>ID:</strong> {{ $role->id }}</p>
                        <p><strong>Name:</strong> {{ $role->name }}</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-md font-medium text-gray-700 mb-2">Assigned Permissions</h4>
                    <div class="bg-gray-50 p-4 rounded-md">
                        @if(!empty($rolePermissions))
                            <div class="flex flex-wrap gap-2">
                                @foreach($rolePermissions as $permission)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mb-1">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p>No permissions assigned</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex space-x-4">
                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring focus:ring-yellow-300 disabled:opacity-25 transition">
                        Edit
                    </a>
                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring focus:ring-red-300 disabled:opacity-25 transition" onclick="return confirm('Are you sure you want to delete this role?')">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 