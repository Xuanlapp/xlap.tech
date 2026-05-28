<?php

namespace App\Console\Commands;

use App\Models\Panini_nba_player;
use App\Services\NbaFetchDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateNbaPlayersData extends Command
{
    protected $signature = 'nba:update-players-data';
    protected $description = '更新所有 NBA 球員的數據到最新年份';

    public function handle()
    {
        $this->info('開始更新球員數據...');

        $players = Panini_nba_player::all();
        $totalPlayers = $players->count();
        $processedPlayers = 0;
        $successCount = 0;
        $failCount = 0;

        $nbaService = new NbaFetchDataService();

        foreach ($players as $player) {
            try {
                $this->info("正在處理球員: {$player->player} (ID: {$player->id})");

                // 更新球員數據
                $result = $nbaService->saveData($player, "update");

                if ($result) {
                    $successCount++;
                    $this->info("成功更新球員: {$player->player}");
                } else {
                    $failCount++;
                    $this->error("更新球員失敗: {$player->player}");
                }

                $processedPlayers++;

                // 每處理10個球員顯示進度
                if ($processedPlayers % 10 === 0) {
                    $this->info("進度: {$processedPlayers}/{$totalPlayers}");
                }

                // 避免請求過於頻繁
                sleep(2);
            } catch (\Exception $e) {
                $failCount++;
                Log::error("處理球員 {$player->id} 時發生錯誤：" . $e->getMessage());
                $this->error("處理球員時發生錯誤: {$player->player} - " . $e->getMessage());
            }
        }

        $this->info("更新完成！");
        $this->info("總球員數: {$totalPlayers}");
        $this->info("成功更新: {$successCount}");
        $this->info("更新失敗: {$failCount}");
    }
}
