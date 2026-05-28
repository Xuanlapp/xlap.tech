<?php

namespace App\Http\Livewire\Modals\Mlb;

use App\Models\panini_mlb_player;
use Illuminate\Support\Facades\Log;
use LivewireUI\Modal\ModalComponent as BaseModalComponent;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use App\Models\panini_mlb_players_league_stat;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Livewire\Traits\Notification;
use App\Services\AddData;

class UpdatePlayerLeagueStat extends BaseModalComponent
{
    use Notification, LivewireAlert;

    public $itemsToRemove = [];

    protected $lap;

    public $logFile = 'mlb_player_update_log.txt';
    public $updating = false;
    public $player_idss;

    public function render()
    {
        return view('livewire.modals.mlb.update-player-league-stat');
    }

    public function boot(AddData $lap)
    {
        $this->lap = $lap;
    }


    public function addPlayers()
    {

        if (empty($this->player_idss)) {
            $this->closeModal();
            $this->showAlertMessage('warning', 'Player not selected!');
        } else {
            if ($this->isLogFileEmpty()) {
                $this->writePlayerIdsToLog();
                $this->updating = false;
            }
            $this->resumeUpdatess();
        }
    }

    protected function resumeUpdatess()
    {
        $playerId = $this->getNextPlayerIdFromLog();
        set_time_limit(600);
        if ($playerId) {
            try {
                $player = panini_mlb_player::where('id', $playerId)->first();
                if ($player) {
                    $player_data = $this->lap->loadApiDatas($player->mlb_player_id);
                    if ($player_data) {
                        $this->lap->saveNewleaguestats($player_data, $player, $player->mlb_player_id);
                        $this->addToItemsToRemove($playerId);
                        $this->removePlayerIdFromLog($playerId);
                    } else {
                        $this->addToItemsToRemove($playerId);
                        throw new Exception('Data is EMPTY');
                    }
                }
            } catch (\Exception $e) {
//                Log::channel('mylog')->error("Update player #{$playerId} Error message:{$e->getMessage()}");
                $this->removePlayerIdFromLog($playerId);
            }
            sleep(1);
            $this->updating = true;
            $this->resumeUpdatess();
//            Log::channel('mylog')->info('now updating ' . $playerId);
//            Log::channel('mylog')->info('updating status ' . $this->updating);
        } else {
            $this->updating = false;
            $this->emit('updateList');
            $this->emit('updateSelectedItems', $this->itemsToRemove);
            // close modal
            $this->closeModal();
            $this->showAlertMessage('success', 'Players Stats Updated!');
        }
    }

    protected function isLogFileEmpty()
    {
        return file_exists(storage_path('app/public/' . $this->logFile)) && filesize(storage_path('app/public/' . $this->logFile)) === 0;
    }

    protected function writePlayerIdsToLog()
    {
        $this->totalPlayers = count($this->player_idss);
        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $this->player_idss));
    }

    protected function getNextPlayerIdFromLog()
    {
        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));

        if ($logContents) {
            $playerIds = explode("\n", $logContents);
            return array_shift($playerIds);
        }

        return null;
    }

    protected function removePlayerIdFromLog($playerId)
    {
        // Xóa ID người chơi đã cập nhật khỏi tệp nhật ký
        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
        $updatedPlayerIds = array_diff(explode("\n", $logContents), [$playerId]);
        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $updatedPlayerIds));
    }

    protected function addToItemsToRemove($player_id)
    {
        $this->itemsToRemove[] = $player_id;
    }
}
