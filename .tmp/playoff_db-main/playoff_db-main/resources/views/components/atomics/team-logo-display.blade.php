@props(['logo', 'tcNumber' => null, 'version' => null])

<div class="flex flex-col items-center justify-center relative">
    @php
        $logoFilePath = 'kdrive/basketball_logo_pdf/'.$logo->logo_file_name_base().$version.'.svg';
        $logoExists = file_exists(public_path($logoFilePath));
    @endphp
    @if($logoExists)
        <img src="{{asset($logoFilePath)}}" alt="" class="w-64 mb-6 absolute z-10"/>
    @else
        <div class="w-64 h-64 mb-6 absolute z-10 flex items-center justify-center bg-gray-100 rounded-md">
            <span class="text-gray-500 font-bold text-lg">No Logo Found</span>
        </div>
    @endif
    <img src="{{asset('kdrive/basketball_logo_pdf/'.$logo->logo_file_name_base().$version.'.svg')}}" alt="" class="w-64 mb-6 absolute z-10"/>

    @if($tcNumber)
        @php
            $tcFilePath = 'kdrive/basketball_tc_swatches/'.$logo->tc_file_name($tcNumber);
            $fileExists = file_exists(public_path($tcFilePath));
        @endphp
        @if($fileExists)
            <iframe
                    src="{{asset($tcFilePath)}}#toolbar=0&navpanes=0&scrollbar=0&view=FitH&zoom=page-fit&transparent=1"
                    width="250px"
                    height="250px"
                    style="border: none; background-color: transparent; overflow: hidden;"
                    allowfullscreen
                    scrolling="no"
            ></iframe>
        @else
            <div
                    style="width: 250px; height: 250px; background-color: #f3f4f6;"
                    class="flex items-center justify-center relative"
            >
                <div class="text-gray-700 text-lg absolute bottom-0 text-center ">No Swatch found</div>
            </div>
        @endif
    @else
        {{ $slot }}
    @endif

    @if(isset($footer))
        {{ $footer }}
    @endif
</div>