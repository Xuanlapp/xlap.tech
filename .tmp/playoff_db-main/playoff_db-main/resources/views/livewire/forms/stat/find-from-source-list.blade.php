<div>
    <div class="px-6 py-4">
        {{$iid}}
        @foreach($players as $player)
        <div wire:key="item-{{ $player->panini_id }}" class="grid grid-cols-12 bg-white shadow-sm p-5 rounded-md hover:shadow-md space-y-5">
            {{-- image and check box section start--}}
            <div class="col-span-4 md:col-span-3 flex-col content-between flex-none mr-5 space-y-3">
                <img src="{{$player->player_img_url()}}" alt="" class="object-cover h-64">
                <div class="text-md text-center">
                    <span class="text-gray-400">MLB ID: </span> {{$player->mlb_player_id}}
                </div>
                <div class="">
                    <p>{{$player->source_full_name}}</p>
                    <p>Name Given: {{$player->source_name_given}}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
