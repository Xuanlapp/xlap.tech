<div class="flex justify_start">
    {{--    @dd($links)--}}

    @foreach ( $links[Route::currentRouteName()] as $key => $link)
        @role($link["permission"])
        <div class="flex-1 text-center">
            <a href="{{route(Route::currentRouteName(),['page'=>$key])}}"
               class="text-lg hover:border-gray-500 hover:border-b-2 hover:text-blue-800 {{Request::route('page')==$key?'pointer-events-none border-b-2 border-blue-500 text-gray-400':'text-blue-400'}}">{{$link['name']}}</a>
        </div>
        @endrole
    @endforeach

</div>
