<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">{{ ($new)?'Assign':'Change' }} MLB ID for {{ $player->player }}</div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <div class="mt-4">
            <div class="mt-2 space-y-2">
                <x-label class="mt-2" value="{{ ($new)?'Assign':'Change' }} MLB Player ID" />
                <x-input wire:keydown.enter="checkPlayerId('{{$player->mlb_player_id}}')" class="block mt-1 w-full" type="text" wire:model="mlb_player_id" autofocus />
                <div class='text-red-300'>Press Enter to validate the player ID before save</div>
            </div>
        </div>
        <div wire:loading.delay>
            Processing...
        </div>
    @if($find_mlb_player)
        <div class="flex space-x-3">
            <img src="https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_639,q_auto:best/v1/people/{{$this->mlb_player_id}}/headshot/67/current" class="h-64 mr-2" alt="">
            <ul class="text-lg text-gray-600">
                <li><span class="text-gray-400">Full name: </span>{{ $find_mlb_player['first_name']." ".$find_mlb_player['last_name'] }}</li>
                <li><span class="text-gray-400">Active: </span>{{ ($find_mlb_player['active'])?"Active":"Retired" }}</li>
            </ul>
        </div>
    @endif
    </div>
    <div class="px-6 py-4 bg-gray-100 text-right">
        <x-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">
            Cancel
        </x-button>
        <x-button wire:click="changeMlbId()" wire:loading.attr="disabled">
            Save
        </x-button>
    </div>
</div>

