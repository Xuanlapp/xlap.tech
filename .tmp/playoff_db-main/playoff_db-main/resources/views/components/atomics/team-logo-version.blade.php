@props(['logo','version'])

<div class="flex flex-col items-center justify-center relative">
    @php
        $logoFilePath = 'kdrive/basketball_logo_pdf/'.$logo->logo_version_name($version);
        $logoExists = file_exists(public_path($logoFilePath));
    @endphp
    @if($logoExists)
        <img src="{{asset($logoFilePath)}}" alt="" class="w-32 absolute z-10 mb-3"/>
    @else
        <div class="w-32 h-32 mb-6 absolute z-10 flex items-center justify-center bg-gray-100 rounded-md">
            <span class="text-gray-500 font-bold text-lg">No Logo Found</span>
        </div>
    @endif
    {{ $slot }}
    @if(isset($footer))
        {{ $footer }}
    @endif
</div>