<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-gray-400 leading-tight text-3xl text-center my-3">
            {{ $page_title[Route::currentRouteName()] }}</h2>
        <div class="flex justify_start">
            @foreach ( $links[Route::currentRouteName()] as $key => $link)
                @role($link["permission"])
                <div class="flex-1 text-center">
                    <a href="{{route(Route::currentRouteName(),['page'=>$key])}}"
                       class="text-lg hover:border-gray-500 hover:border-b-2 hover:text-blue-800 {{Request::route('page')==$key?'pointer-events-none border-b-2 border-blue-500 text-gray-400':'text-blue-400'}}">{{$link['name']}}</a>
                </div>
                @endrole
            @endforeach
        </div>
    </x-slot>
    @livewire('pages.' . Route::currentRouteName() . '.' . $page)
</x-app-layout>
