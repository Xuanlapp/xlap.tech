<?php

namespace App\Http\Livewire\Modals\Mlb;

use App\Models\panini_mlb_player;
use App\Services\AddData;
use Illuminate\Support\Facades\Log;
use LivewireUI\Modal\ModalComponent;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\DataHandler;

class ChangePlayerId extends ModalComponent
{
    use Notification, LivewireAlert;

    public $new;
    public $player_id;
    public $mlb_player_id;
    public $player;
    public $find_mlb_player = null;

    public function mount($player_id, $new)
    {

        $this->new = $new;
        $this->player_id = $player_id;
        $this->player = panini_mlb_player::where('id', $this->player_id)->first();
        $this->mlb_player_id = $this->player->mlb_player_id;
    }

    public function confirmPlayer(DataHandler $dataHandler, $player_id)
    {
        $player = panini_mlb_player::where('id', $this->player_id)->first();
        if ($player->mlb_player_id !== null) {
            $data = $dataHandler->loadApiData($player->mlb_player_id);

            $dataHandler->saveNewPlayer($data, $player, $player->mlb_player_id);
            $this->showAlertMessage('success', 'updated successfully!');
            $this->dispatchBrowserEvent('refresh-page');

        } else {
            $this->showAlertMessage('warning', 'Please assign a MLB ID first!');
        }
    }

    public function checkPlayerId(DataHandler $dataHandler, $old_mlb_id)
    {
        $result = $dataHandler->checkMlbId($this->mlb_player_id, $old_mlb_id);

        if (!$result) {
            $this->find_mlb_player = null;
            $this->showAlertMessage('warning', 'Invalid MLB ID, please try again.');
        } else {
            $this->find_mlb_player = $result['protentialPlayer']; // Cập nhật player tìm được
            $this->showAlertMessage('success', 'MLB Player ID is valid.');
        }
    }

    public function changeMlbId(DataHandler $dataHandler, AddData $addData)
    {
        if ($this->find_mlb_player !== null) {
            // Data Handler Service
            $dataHandler->changeMlbId($this->find_mlb_player, $this->player_id, $this->mlb_player_id);
            $this->showAlertMessage('success', 'updated successfully!');
            // update whole list
            $this->emit('updateList');
            // close modal
            $this->closeModal();
        } else {
            $this->showAlertMessage('warning', 'Need a validated ID!');
        }
    }

    public function render()
    {
        return view('livewire.modals.mlb.change-player-id');
    }
}
