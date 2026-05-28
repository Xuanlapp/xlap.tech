<?php
////
////namespace App\Http\Livewire\Modals\Mlb;
////
////use Exception;
////use App\Services\DataHandler;
////use App\Models\panini_mlb_player;
////use Illuminate\Support\Facades\Log;
////use LivewireUI\Modal\ModalComponent;
////use Illuminate\Support\Facades\Storage;
////use App\Http\Livewire\Traits\Notification;
////use Jantinnerezo\LivewireAlert\LivewireAlert;
////
////class UpdatePlayerStat extends ModalComponent
////{
////    use Notification, LivewireAlert;
////    protected $dataHandler;
////    public $itemsToRemove = [];
////    public $player_ids;
////    public $logFile = 'mlb_player_update_log.txt';
////    public $updating = false;
////    public $totalPlayers = 0;
////
////    public function render()
////    {
////        return view('livewire.modals.mlb.update-player-stat');
////    }
////
////    public function mount($player_ids)
////    {
////        $this->player_ids = array_map('intval', $player_ids);
////        // 檢查 Log 檔案是否存在，如果不存在則建立
////        $this->ensureLogFileExists();
////        // 設置 $updating 的值
////        $this->updating = !$this->isLogFileEmpty();
////    }
////
////    public function boot(DataHandler $dataHandler)
////    {
////        $this->dataHandler = $dataHandler;
////    }
////
////    protected function addToItemsToRemove($player_id)
////    {
////        $this->itemsToRemove[] = $player_id;
////    }
////
////    protected function ensureLogFileExists()
////    {
////        // $logFilePath = storage_path('app/public/' . $this->logFile);
////
////        // 如果 Log 檔案不存在，則建立一個空的 Log 檔案
////        if (!Storage::disk('local')->exists('public/' . $this->logFile)) {
////            Storage::disk('local')->put('public/' . $this->logFile, '');
////        }
////    }
////
////    public function updatePlayers()
////    {
////        // 檢查是否有未完成的更新
////        if (empty($this->player_ids)) {
////            $this->closeModal();
////            $this->showAlertMessage('warning', 'Player not selected!');
////        } else {
////            if ($this->isLogFileEmpty()) {
////                $this->writePlayerIdsToLog();
////                $this->updating = false;
////            }
////            $this->resumeUpdates();
////        }
////    }
////
////    public function resetUpdate()
////    {
////        // 清空 Log 檔案
////        $this->clearLogFile();
////
////        // 重設更新狀態
////        $this->updating = false;
////    }
////
////    protected function clearLogFile()
////    {
////        // 清空 Log 檔案
////        file_put_contents(storage_path('app/public/' . $this->logFile), '');
////    }
////
////    protected function resumeUpdates()
////    {
////        // 進行更新操作
////        $playerId = $this->getNextPlayerIdFromLog();
////
////        set_time_limit(600);
////
////        if ($playerId) {
////            // 更新球員資料
////            try {
////                // 更新操作
////                $player = panini_mlb_player::where('id', $playerId)->first();
////                // dd($player_data);
////                if ($player) {
////                    $player_data = $this->dataHandler->loadApiData($player->mlb_player_id);
////                    if ($player_data) {
////                        $this->dataHandler->saveNewPlayer($player_data, $player, $player->mlb_player_id);
////                        $this->addToItemsToRemove($playerId);
////                        $this->removePlayerIdFromLog($playerId);
////                    } else {
////                        $this->addToItemsToRemove($playerId);
////                        throw new Exception('Data is EMPTY');
////                    }
////                }
////                // 更新完成後，刪除球員 ID
////            } catch (\Exception $e) {
////                // 處理錯誤
////                Log::channel('mylog')->error("Update player #{$playerId} Error message:{$e->getMessage()}");
////                $this->removePlayerIdFromLog($playerId);
////            }
////
////            // 如果還有未完成的更新，繼續
////            sleep(1);
////            $this->updating = true;
////            $this->resumeUpdates();
////            // Log::channel('mylog')->info('now updating ' . $playerId);
////            // Log::channel('mylog')->info('updating status ' . $this->updating);
////        } else {
////            $this->updating = false;
////            $this->emit('updateList');
////            $this->emit('updateSelectedItems', $this->itemsToRemove);
////            // close modal
////            $this->closeModal();
////            $this->showAlertMessage('success', 'Players Stats Updated!');
////        }
////    }
////
////    protected function writePlayerIdsToLog()
////    {
////        // 寫入要更新的球員 ID 到日誌文件
////        // Log::channel('mylog')->info($this->player_ids);
////        $this->totalPlayers = count($this->player_ids);
////        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $this->player_ids));
////    }
////
////    protected function getNextPlayerIdFromLog()
////    {
////        // 從日誌文件中獲取下一個要更新的球員 ID
////        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
////
////        if ($logContents) {
////            $playerIds = explode("\n", $logContents);
////            return array_shift($playerIds);
////        }
////
////        return null;
////    }
////
////    protected function removePlayerIdFromLog($playerId)
////    {
////        // 從日誌文件中刪除已完成更新的球員 ID
////        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
////        $updatedPlayerIds = array_diff(explode("\n", $logContents), [$playerId]);
////        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $updatedPlayerIds));
////    }
////
////    protected function isLogFileEmpty()
////    {
////        // 檢查 Log 檔案是否為空
////        return file_exists(storage_path('app/public/' . $this->logFile)) && filesize(storage_path('app/public/' . $this->logFile)) === 0;
////    }
////
////    /**
////     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
////     */
////    public static function modalMaxWidth(): string
////    {
////        return 'lg';
////    }
////}
//
//
//namespace App\Http\Livewire\Modals\Mlb;
//
//use App\Services\AddData;
//use App\Services\DataHandler;
//use App\Models\panini_mlb_player;
//use Exception;
//use Illuminate\Support\Facades\Log;
//use LivewireUI\Modal\ModalComponent;
//use Illuminate\Support\Facades\Storage;
//use App\Http\Livewire\Traits\Notification;
//use Jantinnerezo\LivewireAlert\LivewireAlert;
//
//class UpdatePlayerStat extends ModalComponent
//{
//    use Notification, LivewireAlert;
//
//    protected $dataHandler;
//    public $itemsToRemove = [];
//    public $player_ids;
//    public $logFile = 'mlb_player_update_log.txt';
//    public $updating = false;
//    public $totalPlayers = 0;
//    protected $lap;
//
//    public function render()
//    {
//        return view('livewire.modals.mlb.update-player-stat');
//    }
//
//    public function mount($player_ids)
//    {
//        $this->player_ids = array_map('intval', $player_ids);
//        $this->ensureLogFileExists();
//        $this->updating = !$this->isLogFileEmpty();
//    }
//
//    public function boot(DataHandler $dataHandler, AddData $lap)
//    {
//        $this->dataHandler = $dataHandler;
//        $this->lap = $lap;
//    }
//
//    public function updatePlayers()
//    {
//        if (empty($this->player_ids)) {
//            $this->closeModal();
//            $this->showAlertMessage('warning', 'Player not selected!');
//        } else {
//            if ($this->isLogFileEmpty()) {
//                $this->writePlayerIdsToLog();
//                $this->updating = false;
//            }
//            $this->resumeUpdates();
//        }
//    }
//
//    public function addPlayers()
//    {
//        if (empty($this->player_ids)) {
//            $this->closeModal();
//            $this->showAlertMessage('warning', 'Player not selected!');
//        } else {
//            if ($this->isLogFileEmpty()) {
//                $this->writePlayerIdsToLog();
//                $this->updating = false;
//            }
//            $this->resumeUpdatess();
//        }
//    }
//
//    protected function resumeUpdates()
//    {
//        $playerId = $this->getNextPlayerIdFromLog();
//        set_time_limit(600);
//
//        if ($playerId) {
//            try {
//                $player = panini_mlb_player::where('id', $playerId)->first();
//                if ($player) {
//                    $player_data = $this->dataHandler->loadApiDataMajor($player->mlb_player_id);
//                    if ($player_data) {
//                        $this->dataHandler->saveNewPlayerMajor($player_data, $player, $player->mlb_player_id);
//                        $this->addToItemsToRemove($playerId);
//                        $this->removePlayerIdFromLog($playerId);
//                    } else {
//                        $this->addToItemsToRemove($playerId);
//                        throw new Exception('Data is EMPTY');
//                    }
//                }
//            } catch (\Exception $e) {
//                Log::channel('mylog')->error("Update player #{$playerId} Error message:{$e->getMessage()}");
//                $this->removePlayerIdFromLog($playerId);
//            }
//            sleep(1);
//            $this->updating = true;
//            $this->resumeUpdates();
//        } else {
//            $this->completeUpdateProcess();
//        }
//    }
//
//    protected function resumeUpdatess()
//    {
//        $playerId = $this->getNextPlayerIdFromLog();
//        set_time_limit(600);
//
//        if ($playerId) {
//            try {
//                $player = panini_mlb_player::where('id', $playerId)->first();
//                if ($player) {
//                    $player_data = $this->lap->loadApiDataMinor($player->mlb_player_id);
//                    if ($player_data) {
//                        $this->lap->saveNewLeagueStats($player_data, $player, $player->mlb_player_id);
//                        $this->addToItemsToRemove($playerId);
//                        $this->removePlayerIdFromLog($playerId);
//                    } else {
//                        $this->addToItemsToRemove($playerId);
//                        throw new Exception('Data is EMPTY');
//                    }
//                }
//            } catch (\Exception $e) {
//                Log::channel('mylog')->error("Update player #{$playerId} Error message:{$e->getMessage()}");
//                $this->removePlayerIdFromLog($playerId);
//            }
//            sleep(1);
//            $this->updating = true;
//            $this->resumeUpdatess();
//        } else {
//            $this->completeUpdateProcess();
//        }
//    }
//
//    protected function completeUpdateProcess()
//    {
//        $this->updating = false;
//        $this->emit('updateList');
//        $this->emit('updateSelectedItems', $this->itemsToRemove);
//        $this->closeModal();
//        $this->showAlertMessage('success', 'Players Stats Updated!');
//    }
//
//    protected function ensureLogFileExists()
//    {
//        if (!Storage::disk('local')->exists('public/' . $this->logFile)) {
//            Storage::disk('local')->put('public/' . $this->logFile, '');
//        }
//    }
//
//    protected function isLogFileEmpty()
//    {
//        return file_exists(storage_path('app/public/' . $this->logFile)) && filesize(storage_path('app/public/' . $this->logFile)) === 0;
//    }
//
//    protected function writePlayerIdsToLog()
//    {
//        $this->totalPlayers = count($this->player_ids);
//        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $this->player_ids));
//    }
//
//    protected function getNextPlayerIdFromLog()
//    {
//        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
//
//        if ($logContents) {
//            $playerIds = explode("\n", $logContents);
//            return array_shift($playerIds);
//        }
//
//        return null;
//    }
//
//    protected function removePlayerIdFromLog($playerId)
//    {
//        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
//        $updatedPlayerIds = array_diff(explode("\n", $logContents), [$playerId]);
//        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $updatedPlayerIds));
//    }
//
//    public static function modalMaxWidth(): string
//    {
//        return 'lg';
//    }
//}


namespace App\Http\Livewire\Modals\Mlb;

use App\Services\AddData;
use App\Services\DataHandler;
use App\Models\panini_mlb_player;
use Exception;
use Illuminate\Support\Facades\Log;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Storage;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class UpdatePlayerStat extends ModalComponent
{
    use Notification, LivewireAlert;

    protected $dataHandler;
    public $itemsToRemove = [];
    public $player_ids;
    public $logFile = 'mlb_player_update_log.txt';
    public $updating = false;
    public $totalPlayers = 0;
    protected $lap;

    public function render()
    {
        return view('livewire.modals.mlb.update-player-stat');
    }

    public function mount($player_ids)
    {
        $this->player_ids = array_map('intval', $player_ids);
        $this->ensureLogFileExists();
        $this->updating = !$this->isLogFileEmpty();
    }

    public function boot(DataHandler $dataHandlers)
    {
        $this->dataHandler = $dataHandlers;
    }

    public function updatePlayers()
    {
        if (empty($this->player_ids)) {
            $this->closeModal();
            $this->showAlertMessage('warning', 'Player not selected!');
        } else {
            if ($this->isLogFileEmpty()) {
                $this->writePlayerIdsToLog();
                $this->updating = false;
            }
            $this->resumeUpdates();
        }
    }

    public function addPlayers()
    {
        if (empty($this->player_ids)) {
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

    public function triggerAddAndUpdate()
    {
        $this->updating = true;
        $this->addPlayers();
        $this->updatePlayers();
    }

    protected function resumeUpdates()
    {
        $playerId = $this->getNextPlayerIdFromLog();
        set_time_limit(600);

        if ($playerId) {
            try {
                $player = panini_mlb_player::where('id', $playerId)->first();
                if ($player) {
                    $player_data = $this->dataHandler->loadApiDataMajor($player->mlb_player_id);
                    if ($player_data) {
                        $this->dataHandler->saveNewPlayerMajor($player_data, $player, $player->mlb_player_id);
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
            $this->resumeUpdates();
        } else {
            $this->completeUpdateProcess();
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
                    $player_data = $this->dataHandler->loadApiDataMinor($player->mlb_player_id);
                    if ($player_data) {
                        $this->dataHandler->saveNewLeagueStats($player_data, $player, $player->mlb_player_id);
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
        } else {
            $this->completeUpdateProcess();
        }
    }

    protected function completeUpdateProcess()
    {
        $this->updating = false;
        $this->emit('updateList');
        $this->emit('updateSelectedItems', $this->itemsToRemove);
        $this->closeModal();
        $this->showAlertMessage('success', 'Players Stats Updated!');
    }

    protected function ensureLogFileExists()
    {
        if (!Storage::disk('local')->exists('public/' . $this->logFile)) {
            Storage::disk('local')->put('public/' . $this->logFile, '');
        }
    }

    protected function isLogFileEmpty()
    {
        return file_exists(storage_path('app/public/' . $this->logFile)) && filesize(storage_path('app/public/' . $this->logFile)) === 0;
    }

    protected function writePlayerIdsToLog()
    {
        $this->totalPlayers = count($this->player_ids);
        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $this->player_ids));
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
        $logContents = file_get_contents(storage_path('app/public/' . $this->logFile));
        $updatedPlayerIds = array_diff(explode("\n", $logContents), [$playerId]);
        file_put_contents(storage_path('app/public/' . $this->logFile), implode("\n", $updatedPlayerIds));
    }

    protected function addToItemsToRemove($player_id)
    {
        $this->itemsToRemove[] = $player_id;
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function resetUpdate()
    {
        $this->clearLogFile();
        $this->updating = false;
    }

    protected function clearLogFile()
    {
        file_put_contents(storage_path('app/public/' . $this->logFile), '');
    }

}
