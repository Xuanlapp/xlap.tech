<?php

namespace App\Http\Livewire\Modals\Mlb;

use App\Models\panini_mlb_player;
use LivewireUI\Modal\ModalComponent;

class PlayerDetail extends ModalComponent
{
    public $player;
    public function mount($player_id)
    {

        $this->player = panini_mlb_player::find($player_id);

    }
    public function render()
    {
        return view('livewire.modals.mlb.player-detail');
    }

    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
