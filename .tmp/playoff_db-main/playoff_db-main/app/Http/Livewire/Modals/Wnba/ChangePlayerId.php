<?php

namespace App\Http\Livewire\Modals\Wnba;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;
use App\Services\WnbaFetchDataService;
use App\Models\Panini_wnba_player;

class ChangePlayerId extends ModalComponent
{
    use Notification, LivewireAlert;
    public $new;
    public $player_id;
    public $wnba_player_id;
    public $player;

    public function mount($player_id, $new)
    {

        $this->new = $new;
        $this->player_id = $player_id;
        $this->player = Panini_wnba_player::find($player_id);
        $this->wnba_player_id = $this->player->wnba_player_id;
    }

    public function changeWnbaId(WnbaFetchDataService $fetchData)
    {
        $this->player->wnba_player_id = $this->wnba_player_id;
        if ($fetchData->saveData($this->player, "reassign")) {
            $this->showAlertMessage('success', 'Updated success!');
            $this->emit('updateList');
            $this->closeModal();
        } else {
            $this->emit('updateList');
            $this->closeModal();
            $this->showAlertMessage('success', 'No data stats and college');
        }
    }

    public function render()
    {
        return view('livewire.modals.wnba.change-player-id');
    }
}
