<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
    <div class=" bg-white justify-between shadow-sm rounded-md p-5 mb-2 space-x-3 mt-3 items-center">
        @foreach ($links as $category => $routes)
            @if ($category != 'dashboard')
                <span class="flex font-semibold text-gray-500 dark:text-gray-400 mt-4">
                                <x-dynamic-component :component="'svg.' . $category"
                                                     class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white mr-2"
                                />
                            {{ ucfirst($category) }}
                </span>
                <div class="grid grid-cols-4 gap-3">
                    @foreach ($routes as $routesKey => $route)
                        <x-button class="my-2">
                            @role($route['permission'])
                            <x-nav-link href="{{ route($routesKey,$route['router']) }}"
                                        :active="request()->routeIs($routesKey)">
                                <img class="h-4 mr-1" src="{{ $route['img'] }}"
                                     alt="">
                                <span class="text-white"> {{ __($route['name']) }}</span>
                            </x-nav-link>
                            @endrole
                        </x-button>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</div>