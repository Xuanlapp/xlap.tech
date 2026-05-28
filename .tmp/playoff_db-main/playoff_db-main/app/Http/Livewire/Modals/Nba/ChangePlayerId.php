<?php

namespace App\Http\Livewire\Modals\Nba;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;
use App\Services\NbaFetchDataService;
use App\Models\Panini_nba_player;

class ChangePlayerId extends ModalComponent
{
    use Notification, LivewireAlert;

    public $new;
    public $player_id;
    public $nba_player_id;
    public $player;
    public $selectNum;
    protected $listeners = ['updateSelect'];

    public function updateSelect($value)
    {
        $this->selectNum = $value;
        $this->dispatchBrowserEvent('initSelect2'); // 讓前端重新初始化 Select2
    }

    public function mount($player_id, $new)
    {
        $this->new = $new;
        $this->player_id = $player_id;
        $this->player = Panini_nba_player::find($player_id);
        $this->nba_player_id = $this->player->nba_player_id;
    }

    public function changeNbaId(NbaFetchDataService $fetchData)
    {
        
        $this->player->nba_player_id = $this->nba_player_id;
        if ($fetchData->saveData($this->player, "reassign")) {
            $this->showAlertMessage('success', 'Updated success!');
            // update whole list
            $this->emit('updateList');
            // close modal
            $this->dispatchBrowserEvent('refresh-page');

            $this->closeModal();
        } else {
            $this->emit('updateList');
            // close modal
            $this->closeModal();
            $this->showAlertMessage('success', 'No data stats and college');
        }
    }

    public function render()
    {
        $this->dispatchBrowserEvent('modalOpened');
        return view('livewire.modals.nba.change-player-id');
    }

}