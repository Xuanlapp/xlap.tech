<?php

namespace App\Http\Controllers;

use App\Models\panini_mlb_player;
use Illuminate\Http\Request;

class MLBPlayerController extends Controller
{
    public function show($id, $name, $team, $full_pos, $status)
    {
        // 查找玩家
        $player = panini_mlb_player::where('panini_id', $id)->first();
        if (!$player) {
            return $this->handleMissingPaniniID($id, $name, $team, $full_pos, $status);
        }
        if ($player->marked == 1) {
            return [
                'stat1' => 'Unapproved Player',
                'stat4' => 'Unapproved Player',
                'stat7' => 'Unapproved Player',
            ];
        } else {
            if (in_array($status, ['Retired', 'Current Player'])) {
                if ($player->stats->isNotEmpty()) {
                    return $this->formatPlayerStats($player);
                } else {
                    return [
                        'stat1' => 'No MLB ID',
                        'stat4' => 'No MLB ID',
                        'stat7' => 'No MLB ID',
                    ];
                }
            } else {
                if ($player->leaguestats->isNotEmpty()) {
                    return $this->formatLeagueStats($player);
                } else {
                    return [
                        'stat1' => 'No MLB ID',
                        'stat4' => 'No MLB ID',
                        'stat7' => 'No MLB ID',
                    ];
                }
            }
        }

    }

    private function handleMissingPaniniID($id, $name, $team, $full_pos, $status)
    {
        $playerByName = panini_mlb_player::where('player', $name)->first();
        if ($playerByName && !$playerByName->panini_id) {
            $playerByName->panini_id = $id;
            $playerByName->save();
            return $this->show($id, $name, $team, $full_pos, $status);
        } elseif ($playerByName && $playerByName->marked == 1) {
            return [
                'stat1' => 'Unapproved Player',
                'stat4' => 'Unapproved Player',
                'stat7' => 'Unapproved Player',
            ];
        } //if player found by name and panini_id
        elseif ($playerByName && $playerByName->panini_id) {
            return [
                'stat1' => 'Panini_id Not Match',
                'stat4' => 'Panini_id Not Match',
                'stat7' => 'Panini_id Not Match',
            ];
        } else {
            return $this->createNewPlayerAndReturnStats($id, $name, $team, $full_pos);
        }
    }

    /**
     * 创建新玩家并返回默认统计信息
     */
    private function createNewPlayerAndReturnStats($id, $name, $team, $full_pos)
    {
        $newPlayerData = [
            'panini_id' => $id,
            'player' => $name,
            'panini_team' => $team,
            'panini_position' => $full_pos,
            'marked' => 0
        ];
        // 创建新玩家
        panini_mlb_player::create($newPlayerData);
        //return default stats
        return [
            'stat1' => 'New Player',
            'stat4' => 'New Player',
            'stat7' => 'New Player',
        ];
    }

    /**
     * 格式化并返回玩家的统计数据
     */

    private function formatLeagueStats($player)
    {
        $header = "Year \t Level \t" . implode("\t", $player->leaguestats->first()->get_stat_titless()) . " ";
        $careerData = $player->show_career();
        $career = $careerData ? "Career\t\t" . implode("\t", $careerData) : '';

        $stat1 = $this->generateStatLines($player, 1);
        $lastYearStat = $player->leaguestats()->orderBy('id', 'desc')->first();
        $last_year = $lastYearStat ? implode("\t", $lastYearStat->show_stats()) . " " : '';

        return [
            // 為了對齊外部使用方（有些會用 head / Career 的概念），這裡同時回傳 header 與 head
            'head' => $header,
            'header' => $header,
            'stat1' => $stat1,
            'stat4' => $this->generateStatLines($player, 4),
            'stat7' => $this->generateStatLines($player, 7),
            'last_year' => $last_year,
            'career' => $career,
        ];
    }

    private function formatPlayerStats($player)
    {

        $header = "Year \t Team \t" . implode("\t", $player->show_stat_title()) . " ";
        $career = "Career\t\t" . implode("\t", $player->show_career());
        return [
            // 為了對齊外部使用方（有些會用 head / Career 的概念），這裡同時回傳 header 與 head
            'head' => $header,
            'header' => $header,
            'stat1' => $this->generateStatLine($player, 1),
            'stat4' => $this->generateStatLine($player, 4),
            'stat7' => $this->generateStatLine($player, 7),
            'last_year' => $this->last_year,
            'career' => $career,
        ];
//        dd($this->last_year);
    }

    private function generateStatLines($player, $years)
    {
        $stat = '';
        foreach ($player->leaguestats()->orderBy('id', 'desc')->take($years)->get() as $year) {
            $stat .= implode("\t", $year->show_stats()) . " ";
        }
        return $stat;
    }

    public $last_year;

    /**
     * 生成给定年份的统计数据行
     */
    private function generateStatLine($player, $years)
    {
        $countyear = 0;

        $stat = '';
        foreach ($player->show_stat_with_quantity($years) as $year) {
            $stat .= implode("\t", $year->show_stat()) . " ";
            $countyear++;
            if ($countyear == 2) {
                $this->last_year = implode("\t", $year->show_stat()) . " ";
            }

        }

        return $stat;
    }
}
