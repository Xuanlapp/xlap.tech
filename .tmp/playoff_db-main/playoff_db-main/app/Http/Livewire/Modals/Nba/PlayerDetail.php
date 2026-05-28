<?php

namespace App\Http\Livewire\Modals\Nba;

use LivewireUI\Modal\ModalComponent;
use App\Models\Panini_nba_player;

class PlayerDetail extends ModalComponent
{
    public $player;

    public function mount($player_id)
    {
        $this->player = Panini_nba_player::find($player_id);
    }
    public function render()
    {
        return view('livewire.modals.nba.player-detail');
    }

    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
