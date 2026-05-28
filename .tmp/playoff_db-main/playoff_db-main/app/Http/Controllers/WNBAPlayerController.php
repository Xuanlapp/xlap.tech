<?php

namespace App\Http\Controllers;

use App\Models\Panini_wnba_player;

class WNBAPlayerController extends Controller
{
    public function show($id, $name, $team, $full_pos, $status)
    {
        // 首先用id查找panini_id
        $player = Panini_wnba_player::where('panini_id', $id)->first();
        
        if (!$player) {
            // 如果用panini_id找不到，才用name查找
            $player = Panini_wnba_player::where('player', $name)->first();
            
            if (!$player) {
                // 兩個都找不到，創建新球員
                return $this->handleMissingPlayer($id, $name, $team, $full_pos, $status);
            } else {
                // 找到球員但沒有panini_id，更新panini_id和其他資料
                $player->panini_id = $id;
                $player->team = $team;
                $player->full_pos = $full_pos;
                $player->status = $status;
                $player->marked = 1;
                $player->save();
            }
        } else {
            // 用panini_id找到球員，更新其他資料（但不更新panini_id）
            $player->team = $team;
            $player->full_pos = $full_pos;
            $player->status = $status;
            $player->marked = 1;
            $player->save();
        }
        
        // 只要有Stats或Career數據就返回，不管marked狀態
        if ($player->hasStats()) {
            // 如果球員有統計數據，返回Header, Stats和Career
            return $this->formatPlayerStats($player);
        } else {
            // 如果球員只有Career數據，只返回Header和Career
            if ($player->hasCareerStats()) {
                return $this->formatCareerOnlyStats($player);
            } else {
                // 沒有任何數據時返回No Stats
                return [
                    'stat1' => 'No Stats',
                ];
            }
        }
    }

    private function handleMissingPlayer($id, $name, $team, $full_pos, $status)
    {
        // 建立新球員，marked = 1因為透過API查詢且有panini_id
        $newPlayerData = [
            'panini_id' => $id,
            'player' => $name,
            'team' => $team,
            'full_pos' => $full_pos,
            'status' => $status,
            'marked' => 1
        ];
        Panini_wnba_player::create($newPlayerData);
        return [
            'stat1' => 'No Stats',
        ];
    }

    /**
     * 格式化並返回球員的統計數據（只有1年stats）
     */
    private function formatPlayerStats($player)
    {
        $header = implode("\t", $player->show_stat_title()) . " ";
        $career = "Career\t" . $player->formatCareerStat();
        
        return [
            'header' => $header,
            'stat1' => $player->formatLatestSeasonStat(),
            'career' => $career,
        ];
    }

    /**
     * 格式化並返回只有Career的統計數據
     */
    private function formatCareerOnlyStats($player)
    {
        $header = implode("\t", $player->show_stat_title()) . " ";
        $career = "Career\t" . $player->formatCareerStat();
        
        return [
            'header' => $header,
            'career' => $career,
        ];
    }
}
