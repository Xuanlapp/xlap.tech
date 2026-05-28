<?php

namespace App\Http\Controllers;

use App\Models\panini_nba_player;
use App\Models\panini_nba_players_college_stats;

//use App\Models\panini_nba_players_stats;

class NBAPlayerController extends Controller
{
    public function show($id, $name, $team, $full_pos, $status)
    {
        $player = panini_nba_player::where('panini_id', $id)->first();
        if (!$player) {
            return $this->handleMissingPaniniID($id, $name, $team, $full_pos, $status);
        }
        if ($player->marked == 3) {
            return [
                'stat1' => 'G-LEAGUE Player',
                'stat4' => 'G-LEAGUE Player',
                'stat7' => 'G-LEAGUE Player',
            ];
        } else {
            if ($player->marked == 1) {
                return [
                    'stat1' => 'Unapproved Player',
                    'stat4' => 'Unapproved Player',
                    'stat7' => 'Unapproved Player',
                ];
            } else {
                if ($player->marked == 0) {
                    return [
                        'stat1' => 'New Player',
                        'stat4' => 'New Player',
                        'stat7' => 'New Player',
                    ];
                } else {
                    if (in_array($status, ['Retired', 'Current Player'])) {
                        if ($player->stats->isNotEmpty()) {
                            return $this->formatPlayerStats($player);
                        } else {
                            return [
                                'stat1' => 'No STAT',
                                'stat4' => 'No STAT',
                                'stat7' => 'No STAT',
                            ];
                        }
                    } else {
                        if ($player->college_stats->isNotEmpty()) {
                            $player_college_stats = panini_nba_players_college_stats::where(
                                'player_id',
                                $player->id
                            )->get();
                            return $this->formatCollegeStats($player_college_stats, $player);
                        } else {
                            return [
                                'stat1' => 'No COLLEGE STAT',
                                'stat4' => 'No COLLEGE STAT',
                                'stat7' => 'No COLLEGE STAT',
                            ];
                        }
                    }
                }
            }
        }
    }

    private function handleMissingPaniniID($id, $name, $team, $full_pos, $status)
    {

        $playerByName = panini_nba_player::where('player', $name)->first();
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
        } elseif ($playerByName && $playerByName->panini_id) {
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
        panini_nba_player::create($newPlayerData);
        return [
            'stat1' => 'New Player',
            'stat4' => 'New Player',
            'stat7' => 'New Player',
        ];
    }

    /**
     * 格式化并返回玩家的统计数据
     */

    private function formatCollegeStats($collegeStats, $player)
    {
        $header = "Season\t Team \t" . implode("\t", $player->college_stats->first()->get_college_stat()) . " ";
        $career = "Career\t\t" . implode("\t", $player->show_college_career());
        // $last_year_career = "Career\t\t" . implode("\t", $player->show_last_year_college_career());
        return [
            'header' => $header,
            'stat1' => $this->generateCollegeStatLine($collegeStats, 1),
            'stat4' => $this->generateCollegeStatLine($collegeStats, 4),
            'stat7' => $this->generateCollegeStatLine($collegeStats, 7),
            'last_year' => $this->last_yearCollege,
            'career' => $career,
            // 'last_year_career' => $last_year_career,
        ];
    }

    public $last_year;
    public $last_yearCollege;

    private function formatPlayerStats($player)
    {
        $header = implode("\t", $player->show_stat_title()) . " ";
        $career = "Career\t\t" . implode("\t", $player->show_career());
        $last_year_career = "Career\t\t" . implode("\t", $player->show_last_year_career());
        return [
            'header' => $header,
            'stat1' => $this->generateStatLine($player, 1),
            'stat4' => $this->generateStatLine($player, 4),
            'stat7' => $this->generateStatLine($player, 7),
            'last_year' => $this->last_year,
            'career' => $career,
            'last_year_career' => $last_year_career,
        ];
    }

    private function generateCollegeStatLine($player, $years)
    {
        $filteredPlayer = $player->filter(function ($item) {
            return $item->career != 1;
        });
        $sortedPlayer = $filteredPlayer->sortByDesc('year');
        $sortedPlayer = $sortedPlayer->take($years);
        $stat = '';
        $countyear = 0;
        foreach ($sortedPlayer as $year) {
            $stat .= implode("\t", $year->show_college_stats()) . " ";
            $countyear++;
            if ($countyear == 2) {
                $this->last_yearCollege = implode("\t", $year->show_college_stats()) . " ";
            }
        }
        return $stat;
    }

    /**
     * 生成给定年份的统计数据行
     */
    private function generateStatLine($player, $years)
    {
        $stat = '';
        $countyear = 0;
        foreach ($player->show_stat_with_quantity($years) as $year) {
            $stat .= implode("\t", $year->show_nba_stat()) . " ";
            $countyear++;
            if ($countyear == 2) {
                $this->last_year = implode("\t", $year->show_nba_stat()) . " ";
            }
        }

        return $stat;
    }
}
