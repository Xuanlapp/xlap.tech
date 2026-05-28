<?php

namespace App\Http\Livewire\Modals\Mlb;

use LivewireUI\Modal\ModalComponent;

class ConfirmDialog extends ModalComponent
{
    public $message;
    public $player_id;

    public function mount($message, $player_id)
    {
        $this->message = $message;
        $this->player_id = $player_id;
    }

    public function render()
    {
        return view('livewire.modals.mlb.confirm-dialog');
    }

    public function confirm()
    {
        $this->emit('confirm', $this->player_id);
        $this->emit('closeModal');
    }
}
