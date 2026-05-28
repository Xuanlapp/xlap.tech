<div>
    {{--    @if($url )--}}

    {{--        <img src="{{ $url }}" alt="Image Modal" class="w-full h-auto rounded-md"--}}
    {{--             onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">--}}
    {{--    @else--}}
    {{--        <p>No image available</p>--}}
    {{--    @endif--}}
    <img src="{{ $url }}" alt="Image Modal" class="w-full h-auto rounded-md"
         onerror="this.onerror=null; this.src='{{ asset('images/miscellaneous/no_image.png') }}';">
</div>