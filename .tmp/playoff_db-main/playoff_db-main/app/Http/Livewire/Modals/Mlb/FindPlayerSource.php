<?php

namespace App\Http\Livewire\Modals\Mlb;

use App\Models\Source_player;
use Illuminate\Support\Facades\Log;
use LivewireUI\Modal\ModalComponent;
use App\Http\Livewire\Traits\Notification;
use App\Http\Livewire\Traits\MlbDownloadData;
use App\Models\MlbPlayer;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class FindPlayerSource extends ModalComponent
{
    use MlbDownloadData, LivewireAlert, Notification;

    public $player_full_name;
    public $panini_id;
    public $match_players;
    public $player;

    public function mount($panini_id, $player_full_name)
    {
        // Log::channel('mylog')->info($player_full_name);
        $this->player_full_name = $player_full_name;
        $this->panini_id = $panini_id;
    }

    public function render()
    {
        $this->match_players = Source_player::where('source_full_name', $this->player_full_name)->get();
        // Log::channel('mylog')->info($this->match_players);
        // dd();
        $this->player = MlbPlayer::where('panini_id', $this->panini_id)->first();

        foreach ($this->match_players as $key => $player) {
            foreach ($this->loadApiData($player->mlb_player_id) as $arr_key => $value) {
                $this->match_players[$key][$arr_key] = $value;
            }
        }
        // Log::channel('mylog')->info(json_decode($this->match_players->teams_played));

        return view('livewire.modals.mlb.find-player-source');
    }

    public function matchPlayer($mlb_id)
    {
        // set MLB ID to $player
        $this->player->player_id = $mlb_id;
        $this->player->status = 3;
        $this->player->save();
        // emit update new player list
        $this->emit('updateList');
        // Show success message
        $this->showAlertMessage('success', "<span class='text-green-500 text-xl'>{$this->player->panini_full_name}</span> has been matched!");

        // close modal
        $this->closeModal();
    }

    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
