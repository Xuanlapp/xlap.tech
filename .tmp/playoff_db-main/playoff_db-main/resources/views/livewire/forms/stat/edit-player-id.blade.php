<div>
    <div class="px-6 py-4">
        <div class="text-lg">
            Changing MLB Player ID for <br><span class="text-green-500 text-2xl">{{$player->panini_full_name}}</span> <br>who plays for 
            <div class="text-green-500 text-2xl flex content-center">
                @if ($player->team->team_num !== null)
                    <img class="h-8 mr-2" src="{{"https://www.mlbstatic.com/team-logos/team-cap-on-light/".$player->team->team_num.".svg"}}" alt="">
                @endif
                {{$player->panini_team}}
            </div>
        </div>
        <div class="mt-4">
            <div class="mt-2 space-y-2">            
                <x-label class="mt-2" value="Current MLB Player ID" />
                <x-input class="block mt-1 w-full" type="text" wire:model="player_id" autofocus />
            </div>
        </div>
    </div>

    <div class="px-6 py-4 bg-gray-100 text-right">
        <x-secondary-button wire:click="$emit('close_modal')" wire:loading.attr="disabled">
            Cancel
        </x-button>
        <x-button wire:click="updatePlayerId()" wire:loading.attr="disabled">
            Change
        </x-button>
    </div>
</div>
