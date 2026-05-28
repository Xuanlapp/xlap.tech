<?php

namespace App\Services;

use App\Models\Nba_team;
use App\Models\PaniniNbaPlayerStats;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class NbaFetchDataService
{
    use  LivewireAlert;

    /**
     * saveData
     *
     * @param mixed $player
     * @param string $saveMode "reassign"
     * @return void
     */
    public function saveData($player, $saveMode)
    {


        $nbaStatResult = $this->SavedataStat($player, $saveMode);
        // $collegeStatResult = $this->SavedataCollegestat($player, $saveMode);
        // if ($nbaStatResult || $collegeStatResult) {
        if ($nbaStatResult) {
            return true;
        } else {
            $player->marked = 4;
            $player->save();
            return false;
        }
    }

    // public function SavedataCollegestat($player, $saveMode)
    // {
    //     if ($player->nba_player_id == null) {
    //         Log::info('球員沒有 NBA ID，跳過更新');
    //         return false;
    //     }

    //     try {
    //         $playerStats = $player->stats();
    //         $statIds = $playerStats->pluck('id');
    //         if (!$statIds->isEmpty()) {
    //             foreach ($statIds as $statId) {
    //                 $this->UploadTeam($statId);
    //             }
    //         }

    //         $dataTotalsCollegeSeason = $this->handleDataTotalsCollegeSeason($player->nba_player_id);
    //         //dd($dataTotalsCollegeSeason);
    //         if ($dataTotalsCollegeSeason) {
    //             // Delete all existing records for the player
    //             if ($player->college_stats()->count() > 0) {
    //                 $player->college_stats()->delete();
    //             }

    //             // Loop through each season's stats and save them
    //             foreach ($dataTotalsCollegeSeason['stats'] as $data) {
    //                 $statData = [
    //                     'player_id' => $data['player_id'],
    //                     'year' => $data['season_id'],
    //                     'team' => $data['school_name'],
    //                     'g' => $data['gp'],
    //                     'fgm' => $data['fgm'],
    //                     'fga' => $data['fga'],
    //                     'fg%' => $data['fg_pct'],
    //                     'ftm' => $data['ftm'],
    //                     'fta' => $data['fta'],
    //                     'ft%' => $data['ft_pct'],
    //                     '3pm' => $data['fg3m'],
    //                     'rpg' => $data['reb'],
    //                     'apg' => $data['ast'],
    //                     'stl' => $data['stl'],
    //                     'blk' => $data['blk'],
    //                     'pts' => $data['pts'],
    //                     'ppg' => $data['ppg']
    //                 ];

    //                 $player->savePlayerCollegeStat($statData);
    //             }

    //             // 保存当前的college_career_stats到last_year_college_career_stats
    //             if ($player->college_career_stats) {
    //                 $player->last_year_college_career_stats = $player->college_career_stats;
    //             }

    //             // 将career统计数据保存到college_career_stats字段
    //             $careerData = $dataTotalsCollegeSeason['career'];
    //             $careerStats = [
    //                 'G' => $careerData['gp'],
    //                 'FG%' => $this->convertPercentToDecimal($careerData['fg_pct']),
    //                 'FT%' => $this->convertPercentToDecimal($careerData['ft_pct']),
    //                 '3PM' => $careerData['fg3m'],
    //                 'RPG' => $careerData['reb'],
    //                 'APG' => $careerData['ast'],
    //                 'STL' => $careerData['stl'],
    //                 'BLK' => $careerData['blk'],
    //                 'PTS' => $careerData['pts'],
    //                 'PPG' => $careerData['ppg']
    //             ];

    //             $player->college_career_stats = $careerStats;
    //             $player->career_stats_last_updated_at = now();
    //             $player->marked = 4;
    //             $player->save();

    //             // 計算去年的大學生涯統計數據
    //             $this->calculateLastYearCollegeCareerStats($player);

    //             $this->getPlayerLatestYear($player);
    //             return true;
    //         } else {
    //             return false;
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('球員大學數據更新失敗', [
    //             'player_id' => $player->id,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return false;
    //     }
    // }


    public function SavedataStat($player, $saveMode)
    {
        try {
            $data = $this->handleData($player->nba_player_id);
            if (!$data) {
                Log::error('無法獲取球員數據', ['player_id' => $player->id]);
                return false;
            }

            // 更新團隊信息

            $playerStats = $player->stats();
            $statIds = $playerStats->pluck('id');


            if (!$statIds->isEmpty()) {
                foreach ($statIds as $statId) {
                    try {
                        $this->UploadTeam($statId);
                    } catch (\Exception $e) {
                        Log::error('UploadTeam執行失敗', [
                            'stat_id' => $statId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                Log::warning('沒有找到統計ID，無法更新團隊信息', ['player_id' => $player->id]);
            }

            if ($saveMode == "update") {
                $this->updatePlayerStats($player, $data['stats']);
            } else {
                $this->replacePlayerStats($player, $data['stats']);
            }

            // 更新生涯數據
            $this->updateCareerStats($player, $data['career']);

            // 計算去年的生涯統計數據
            $this->calculateLastYearCareerStats($player);

            // 更新球員 Active 狀態
            $this->updatePlayerActiveStatus($player);

            $player->marked = 4;
            $player->save();
            $this->getPlayerLatestYear($player);

            // 確保更新新添加的數據的team_full字段

            $newPlayerStats = $player->stats()->get();
            foreach ($newPlayerStats as $stat) {
                if (empty($stat->team_full)) {

                    $this->UploadTeam($stat->id);
                }
            }


            return true;
        } catch (\Exception $e) {
            Log::error('球員數據更新失敗', [
                'player_id' => $player->nba_player_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function updatePlayerStats($player, $newStats)
    {
        $playerAllStats = $player->stats;

        foreach ($newStats as $newStat) {
            // 檢查是否已存在相同年份的數據
            $existingStat = $playerAllStats->firstWhere('year', $newStat['year']);

            if ($existingStat) {
                // 檢查數據是否有變化
                if ($this->hasStatsChanged($existingStat, $newStat)) {
                    // 記錄數據變化
                    $this->logStatsChanges($existingStat, $newStat);

                    // 處理中途轉隊
                    if ($this->isMultiTeamSeason($newStat)) {
                        $newStat = $this->handleMultiTeamStats($newStat);
                    }

                    // 更新現有數據
                    $existingStat->update($newStat);
                    $existingStat->last_updated_at = now();
                    $existingStat->save();
                }
            } else {
                // 處理新賽季數據
                if ($this->isMultiTeamSeason($newStat)) {
                    $newStat = $this->handleMultiTeamStats($newStat);
                }
                $newStat['last_updated_at'] = now();
                $player->savePlayerStat($newStat);
            }
        }
    }

    private function replacePlayerStats($player, $newStats)
    {
        // 備份舊數據
        $oldStats = $player->stats()->get();
        foreach ($oldStats as $oldStat) {
            $this->logStatsChanges($oldStat, null, 'deleted');
        }

        // 刪除舊數據
        $player->stats()->delete();

        // 保存新數據
        foreach ($newStats as $stat) {
            if ($this->isMultiTeamSeason($stat)) {
                $stat = $this->handleMultiTeamStats($stat);
            }
            $stat['last_updated_at'] = now();
            $player->savePlayerStat($stat);
        }
    }

    private function updateCareerStats($player, $careerData)
    {
        // // 保存去年的生涯數據
        // if ($player->career_stats) {
        //     $player->last_year_career_stats = $player->career_stats;
        // }

        // 更新新的生涯数据到career_stats字段
        $careerStats = [
            'G' => $careerData['g'],
            'FG%' => $this->convertPercentToDecimal($careerData['fg%']),
            'FT%' => $this->convertPercentToDecimal($careerData['ft%']),
            '3PM' => $careerData['3pm'],
            'RPG' => $careerData['rpg'],
            'APG' => $careerData['apg'],
            'STL' => $careerData['stl'],
            'BLK' => $careerData['blk'],
            'PTS' => $careerData['pts'],
            'PPG' => $careerData['ppg']
        ];

        $player->career_stats = $careerStats;
        $player->career_stats_last_updated_at = now();
    }

    /**
     * 将百分比数值转换为小数形式
     */
    private function convertPercentToDecimal($percentValue)
    {
        // 检查是否已经是小数形式（小于1的值）
        if ($percentValue < 1) {
            return $percentValue;
        }

        // 转换百分比为小数，例如：48.1 -> 0.481
        return round($percentValue / 100, 3);
    }

    private function hasStatsChanged($oldStat, $newStat)
    {
        $attributes = [
            'g',
            'fg%',
            'ft%',
            '3pm',
            'rpg',
            'apg',
            'stl',
            'blk',
            'pts',
            'ppg',
            'fgm',
            'fga',
            'ftm',
            'fta'
        ];

        foreach ($attributes as $attribute) {
            if (
                isset($newStat[$attribute]) &&
                abs($oldStat->{$attribute} - $newStat[$attribute]) > 0.001
            ) {
                return true;
            }
        }

        return false;
    }

    private function logStatsChanges($oldStat, $newStat, $action = 'updated')
    {
        $changes = [];
        if ($action === 'deleted') {
            $changes = [
                'action' => 'deleted',
                'old_data' => $oldStat->toArray(),
                'timestamp' => now()
            ];
        } else {
            $attributes = [
                'g',
                'fg%',
                'ft%',
                '3pm',
                'rpg',
                'apg',
                'stl',
                'blk',
                'pts',
                'ppg',
                'fgm',
                'fga',
                'ftm',
                'fta'
            ];

            foreach ($attributes as $attribute) {
                if (
                    isset($newStat[$attribute]) &&
                    abs($oldStat->{$attribute} - $newStat[$attribute]) > 0.001
                ) {
                    $changes[$attribute] = [
                        'old' => $oldStat->{$attribute},
                        'new' => $newStat[$attribute]
                    ];
                }
            }

            if (!empty($changes)) {
                $changes['action'] = 'updated';
                $changes['timestamp'] = now();
            }
        }

        if (!empty($changes)) {
            Log::info('球員統計數據變化', [
                'player_id' => $oldStat->player_id,
                'year' => $oldStat->year,
                'changes' => $changes
            ]);
        }
    }

    private function isMultiTeamSeason($stat)
    {
        return isset($stat['team']) && strpos($stat['team'], '/') !== false;
    }

    private function handleMultiTeamStats($stat)
    {
        if (!$this->isMultiTeamSeason($stat)) {
            return $stat;
        }

        $teams = explode('/', $stat['team']);
        $stat['is_combined_team_stat'] = true;
        $stat['original_team_stats'] = json_encode([
            'teams' => $teams,
            'original_team' => $stat['team']
        ]);

        return $stat;
    }

    private function handleDataTotalsCollegeSeason($player_id)
    {
        $SeasonTotalsCollegeSeason = [
            'player_id' => 0,
            'season_id' => 1,
            'league_id' => 2,
            'organization_id' => 3,
            'school_name' => 4,
            'player_age' => 5,
            'gp' => 6,
            'gs' => 7,
            'min' => 8,
            'fgm' => 9,
            'fga' => 10,
            'fg_pct' => 11,
            'fg3m' => 12,
            'fg3a' => 13,
            'fg3_pct' => 14,
            'ftm' => 15,
            'fta' => 16,
            'ft_pct' => 17,
            'oreb' => 18,
            'dreb' => 19,
            'stl' => 22,
            'blk' => 23,
            'tov' => 24,
            'pf' => 25,
            'pts' => 26
        ];

        $SeasonTotalsCollegeSeasongreen = [
            'reb' => 20,
            'ast' => 21,
            'pts' => 26
        ];

        $CareerTotalsCollegeSeason = [
            'player_id' => 0,
            'league_id' => 1,
            'organization_id' => 2,
            'gp' => 3,
            'gs' => 4,
            'min' => 5,
            'fgm' => 6,
            'fga' => 7,
            'fg_pct' => 8,
            'fg3m' => 9,
            'fg3a' => 10,
            'fg3_pct' => 11,
            'ftm' => 12,
            'fta' => 13,
            'ft_pct' => 14,
            'oreb' => 15,
            'dreb' => 16,
            'stl' => 19,
            'blk' => 20,
            'tov' => 21,
            'pf' => 22,
            'pts' => 23
        ];

        $CareerTotalsCollegeSeasongreen = [
            'reb' => 17,
            'ast' => 18,
            'pts' => 23
        ];

        // Load total career and season data
        //        $carreerData = $this->loadApiData($player_id, 'Totals');
        $totalsData = $this->loadApiData($player_id, 'Totals');

        // Extract career totals
        $careerTotals = null;
        foreach ($totalsData->resultSets as $resultSet) {
            if ($resultSet->name === 'CareerTotalsCollegeSeason') {
                $careerTotals = $resultSet;
                break;
            }
        }

        // Extract season totals
        $seasonTotalsRegular = null;
        foreach ($totalsData->resultSets as $resultSet) {
            if ($resultSet->name === 'SeasonTotalsCollegeSeason') {
                $seasonTotalsRegular = $resultSet;
                break;
            }
        }

        if ($seasonTotalsRegular && count($seasonTotalsRegular->rowSet) !== 0) {
            // Load per-game data for both season and career
            $perGameData = $this->loadApiData($player_id, 'PerGame');
            $perGameStats = [];
            $perGameCareerStats = [];

            foreach ($perGameData->resultSets as $resultSet) {
                if ($resultSet->name === 'SeasonTotalsCollegeSeason') {
                    $perGameStats = $resultSet->rowSet;
                }
                if ($resultSet->name === 'CareerTotalsCollegeSeason') {
                    $perGameCareerStats = $resultSet->rowSet[0];
                }
            }

            // Process season totals
            foreach ($seasonTotalsRegular->rowSet as $key => $value) {
                foreach ($SeasonTotalsCollegeSeason as $Hkey => $Hvalue) {
                    $player['stats'][$key][$Hkey] = $value[$Hvalue];
                }
                $player['stats'][$key]['pts'] = $value[$SeasonTotalsCollegeSeason['pts']];
                $player['stats'][$key]['ppg'] = $perGameStats[$key][$SeasonTotalsCollegeSeasongreen['pts']];

                // Save other per-game stats
                foreach ($SeasonTotalsCollegeSeasongreen as $Hkey => $Hvalue) {
                    if ($Hkey !== 'pts') {
                        $player['stats'][$key][$Hkey] = $perGameStats[$key][$Hvalue];
                    }
                }
            }

            // Process career totals
            if ($careerTotals && count($careerTotals->rowSet) > 0) {
                $totalsCareer = $careerTotals->rowSet[0];

                foreach ($CareerTotalsCollegeSeason as $Hkey => $Hvalue) {
                    $player['career'][$Hkey] = $totalsCareer[$Hvalue];
                }

                $player['career']['pts'] = $totalsCareer[$CareerTotalsCollegeSeason['pts']];
                $player['career']['ppg'] = $perGameCareerStats[$CareerTotalsCollegeSeasongreen['pts']];

                // Save other career per-game stats
                foreach ($CareerTotalsCollegeSeasongreen as $Hkey => $Hvalue) {
                    if ($Hkey !== 'pts') {
                        $player['career'][$Hkey] = $perGameCareerStats[$Hvalue];
                    }
                }
            }
            //            dd($player);

            // Reverse the stats array to maintain the order
            $player['stats'] = array_reverse($player['stats']);

            return $player;
        } else {
            return false;
        }
    }


    private function getPlayerLatestYear($player)
    {
        if ($player->stats()->count()) {
            $latestStat = $player->stats->last();
            if ($latestStat && $latestStat->year) {
                $full_latest_year = $latestStat->year;
                $latest_year_parts = explode("-", $full_latest_year);
                if (count($latest_year_parts) > 0) {
                    $latest_year = (int)$latest_year_parts[0] + 1;
                    $player->latest_season = $latest_year;
                    $player->save();
                }
            }
        }
    }


    private function handleData($player_id)
    {
        $totalsHeaderArr = [
            'year' => 1,
            'team' => 4,
            'g' => 6,
            'fgm' => 9,
            'fga' => 10,
            'ftm' => 15,
            'fta' => 16,
            'fg%' => 11,
            'ft%' => 17,
            '3pm' => 12,
            'rpg' => 20,
            'apg' => 21,
            'stl' => 22,
            'blk' => 23,
            'pts' => 26
        ];
        $perGameHeaderArr = ['rpg' => 20, 'apg' => 21, 'ppg' => 26];

        $totalsCareerHeaderArr = [
            'g' => 3,
            'fgm' => 6,
            'fga' => 7,
            'ftm' => 12,
            'fta' => 13,
            'fg%' => 8,
            'ft%' => 14,
            '3pm' => 9,
            'rpg' => 17,
            'apg' => 18,
            'stl' => 19,
            'blk' => 20,
            'pts' => 23
        ];
        $perGameCareerHeaderArr = ['rpg' => 17, 'apg' => 18, 'ppg' => 23];

        // get Totals Regular Season Data
        // PerGame/Totals
        $totalsData = $this->loadApiData($player_id, 'Totals');
        foreach ($totalsData->resultSets as $resultSet) {
            if ($resultSet->name === 'SeasonTotalsRegularSeason') {
                $seasonTotalsRegular = $resultSet;
                break;
            }
        }
        // if resultSets -> 0->name = SeasonTotalsRegularSeason
        if ($seasonTotalsRegular->name == 'SeasonTotalsRegularSeason') {
            if (count($totalsData->resultSets[0]->rowSet) !== 0) {
                // get PerGame regular Season Data
                $perGameData = $this->loadApiData($player_id, 'PerGame');
                // extract stats data for Totals
                $totalsStats = $seasonTotalsRegular->rowSet;
                foreach ($perGameData->resultSets as $resultSet) {
                    if ($resultSet->name === 'SeasonTotalsRegularSeason') {
                        $perGameStats = $resultSet->rowSet;
                        break;
                    }
                }
                //                $perGameStats = $perGameData->resultSets[0]->rowSet;

                foreach ($totalsStats as $key => $value) {
                    // Now we are looping Regular Totals with season and Team
                    foreach ($totalsHeaderArr as $Hkey => $Hvalue) {
                        $player['stats'][$key][$Hkey] = $value[$Hvalue];
                    }

                    // 特例：NBA.com 回傳的 TEAM_ABBREVIATION 為 GSW 時，改成 SF
                    if (isset($player['stats'][$key]['team']) && $player['stats'][$key]['team'] === 'GSW') {
                        $player['stats'][$key]['team'] = 'SF';
                    }

                    $player['stats'][$key]['ppg'] = '';
                    // Now we are looping Regular PerGame with season and Team
                    foreach ($perGameHeaderArr as $Hkey => $Hvalue) {
                        $player['stats'][$key][$Hkey] = $perGameStats[$key][$Hvalue];
                    }
                }
                foreach ($totalsData->resultSets as $resultSet) {
                    if ($resultSet->name === 'CareerTotalsRegularSeason') {
                        $totalsCareer = $resultSet->rowSet[0];
                        break;
                    }
                }
                foreach ($perGameData->resultSets as $resultSet) {
                    if ($resultSet->name === 'CareerTotalsRegularSeason') {
                        $perGameCareer = $resultSet->rowSet[0];
                        break;
                    }
                }
                //                $totalsCareer = $totalsData->resultSets[1]->rowSet[0];
                //dd($totalsData);
                //                $perGameCareer = $perGameData->resultSets[1]->rowSet[0];

                foreach ($totalsCareerHeaderArr as $Hkey => $Hvalue) {
                    $player['career'][$Hkey] = $totalsCareer[$Hvalue];
                }
                $player['career']['ppg'] = '';
                foreach ($perGameCareerHeaderArr as $Hkey => $Hvalue) {
                    $player['career'][$Hkey] = $perGameCareer[$Hvalue];
                }
                // reverse the array to fit the convension for handle multi team
                $player['stats'] = array_reverse($player['stats']);
                $player = $this->handleMultiTeam($player);

                // reverse it back after
                $player['stats'] = array_reverse($player['stats']);

                return $player;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function handleMultiTeam(
        $data
    ) {
        $stats = $data['stats'];

        $arr_team = [];
        $popout_indexs = [];
        $target_stat_year = '';
        $tot_index = '';
        $flag = 0;

        foreach ($stats as $index => $stat) {
            if ($stat['team'] == "TOT") {
                if ($flag) {
                    if ($stat['year'] != $target_stat_year) {
                        /*
                        * Process Data
                        */
                        // Store team
                        $data['stats'][$tot_index]['team'] = implode("/", $arr_team);
                        // clear flag
                        $flag = 0;
                        // clear Target ID
                        $tot_index = '';
                        // clear Target Year
                        $target_stat_year = '';
                        // clear teamArr
                        $arr_team = [];
                        /*
                        * Prepare data
                        */
                        // Store target ID
                        $tot_index = $index;
                        // Store Target Year
                        $target_stat_year = $stat['year'];
                        // Set Flag
                        $flag = 1;
                    }
                } else { // flag off
                    if ($stat['year'] != $target_stat_year) {
                        //**** Prepare data
                        // Store index number
                        $tot_index = $index;
                        // Store Target Year
                        $target_stat_year = $stat['year'];
                        // Set Flag
                        $flag = 1;
                    }
                }
            } else {
                if ($flag) {
                    if ($stat['year'] == $target_stat_year) {
                        /*
                        * Add Team to teamArr
                        */
                        $arr_team[] = $stat['team'];
                        // Store index number, for late to remove
                        $popout_indexs[] = $index;

                        if ($index + 1 == count($stats)) {
                            /*
                            * Process Data
                            */
                            // Store team
                            $data['stats'][$tot_index]['team'] = implode("/", $arr_team);
                            // clear flag
                            $flag = 0;
                            // clear Target ID
                            $tot_index = '';
                            // clear Target Year
                            $target_stat_year = '';
                            // clear teamArr
                            $arr_team = [];
                        }
                    } else {
                        /*
                        * Process Data
                        */
                        // Store team
                        $data['stats'][$tot_index]['team'] = implode("/", $arr_team);
                        // clear flag
                        $flag = 0;
                        // clear Target ID
                        $tot_index = '';
                        // clear Target Year
                        $target_stat_year = '';
                        // clear teamArr
                        $arr_team = [];
                    }
                }
            }
        }

        // pop out all the index marked
        foreach ($popout_indexs as $index) {
            unset($data['stats'][$index]);
        }

        $data['stats'] = array_values($data['stats']); // 重新索引数组
        // Log::channel('mylog')->info($data['stat']);


        return $data;
    }

    private function loadApiData(
        $player_id,
        $perMode
    ) {
        $url = "https://stats.nba.com/stats/playercareerstats?LeagueID=00&PerMode=" . $perMode . "&PlayerID=" . $player_id;
        $retryCount = 0;
        $maxRetries = 3;
        
        while ($retryCount < $maxRetries) {
            try {
                // 使用與瀏覽器完全相同的 headers（基於截圖的成功請求）
                $headers = [
                    'Accept' => '*/*',
                    'Accept-Encoding' => 'gzip, deflate, br, zstd',
                    'Accept-Language' => 'en-US,en;q=0.9,zh-TW;q=0.8,zh;q=0.7,zh-CN;q=0.6',
                    'Connection' => 'keep-alive',
                    'Host' => 'stats.nba.com',
                    'Origin' => 'https://www.nba.com',
                    'Referer' => 'https://www.nba.com/',
                    'Sec-Ch-Ua' => '"Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"macOS"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                ];

                $client = new Client([
                    'headers' => $headers,
                    'verify' => false,
                    'allow_redirects' => true,
                ]);
                
                Log::info('嘗試直接 API 調用', [
                    'player_id' => $player_id,
                    'perMode' => $perMode,
                    'retry' => $retryCount + 1
                ]);
                
                $response = $client->request('GET', $url, [
                    'timeout' => 30,
                    'connect_timeout' => 10,
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $data = json_decode($response->getBody());
                    
                    Log::info('✓ 直接 API 調用成功', [
                        'player_id' => $player_id,
                        'perMode' => $perMode,
                        'retry_count' => $retryCount
                    ]);
                    
                    return $data;
                } else {
                    Log::warning('API 返回非成功狀態碼', [
                        'player_id' => $player_id,
                        'status_code' => $response->getStatusCode()
                    ]);
                    
                    $request = new \GuzzleHttp\Psr7\Request('GET', $url);
                    throw new RequestException('Unsuccessful response', $request);
                }
            } catch (RequestException $e) {
                $retryCount++;
                
                Log::warning('API 請求失敗', [
                    'player_id' => $player_id,
                    'perMode' => $perMode,
                    'retry_count' => $retryCount,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage()
                ]);
                
                if ($retryCount < $maxRetries) {
                    $sleepTime = pow(2, $retryCount); // 指數退避：2秒、4秒、8秒
                    Log::info("等待 {$sleepTime} 秒後重試...");
                    sleep($sleepTime);
                }
            } catch (\Exception $e) {
                Log::error('未預期的錯誤', [
                    'player_id' => $player_id,
                    'error' => $e->getMessage()
                ]);
                break;
            }
        }
        
        // 直接 API 調用失敗後，使用 Python 備選方案
        Log::info('直接 API 調用全部失敗，切換到 Python nba_api', [
            'player_id' => $player_id,
            'perMode' => $perMode
        ]);
        
        return $this->loadApiDataViaPython($player_id, $perMode);
    }

    public function UploadTeam($id)
    {
        $player = PaniniNbaPlayerStats::find($id);

        // 检查是否找到了统计记录
        if (!$player) {
            Log::error('無法找到統計記錄', ['stat_id' => $id]);
            return;
        }

        if (strpos($player->team, '/') !== false) {
            $teams = explode('/', $player->team);


            // Look up both teams
            $team1 = Nba_team::where('team_abb', $teams[0])->first();
            $team2 = Nba_team::where('team_abb', $teams[1])->first();

            if ($team1 && $team2) {
                $stat_name = $team1->stat_name . '/' . $team2->stat_name;

                PaniniNbaPlayerStats::where('id', $player->id)->update([
                    'team_full' => $stat_name,
                ]);
            } else {
                Log::warning('找不到完整的球隊信息', [
                    'team1_found' => !is_null($team1),
                    'team2_found' => !is_null($team2),
                    'team_abb1' => $teams[0],
                    'team_abb2' => $teams[1]
                ]);
            }
        } else {
            // Handle the case where the team is not split (i.e., single team)
            $team = Nba_team::where('team_abb', $player->team)->first();
            if ($team) {

                PaniniNbaPlayerStats::where('id', $player->id)->update([
                    'team_full' => $team->stat_name,
                ]);
            } else {
                Log::warning('找不到球隊信息', ['team_abb' => $player->team]);
            }
        }
    }

    private function updatePlayerActiveStatus($player)
    {
        if (!$player->latest_year) {
            $player->active = false;
            $player->save();
            return;
        }

        $currentYear = date('Y');
        $latestSeasonYear = (int)$player->latest_year;

        // 如果最新賽季在四年內，設置為活躍
        $player->active = ($currentYear - $latestSeasonYear <= 4);
        $player->save();

        // Log::info('球員活躍狀態更新', [
        //     'player_id' => $player->nba_player_id,
        //     'latest_year' => $latestSeasonYear,
        //     'current_year' => $currentYear,
        //     'active' => $player->active
        // ]);
    }

    /**
     * 獲取當前NBA賽季格式（如2024-25）
     */
    private function getCurrentNbaSeason()
    {
        // 2024-2025賽季為當前賽季
        $currentSeason = '2024-25';

        // 原有的動態判斷邏輯保留，但做細微調整
        // NBA賽季一般從10月開始到次年4月（常規賽）
        $currentYear = date('Y');
        $currentMonth = date('n'); // 1-12的月份數字

        // 如果是在7月到9月，則應該是下一賽季
        if ($currentMonth >= 7 && $currentMonth <= 9) {
            // 下一賽季為2025-26
            $seasonStartYear = 2025;
        } else {
            // 當前賽季為2024-25
            $seasonStartYear = 2024;
        }

        $nextYearSuffix = substr(($seasonStartYear + 1), 2, 2);
        $calculatedSeason = $seasonStartYear . '-' . $nextYearSuffix;

        // 記錄賽季計算邏輯，以便調試
        // Log::info('NBA賽季計算', [
        //     'hardcoded_current_season' => $currentSeason,
        //     'calculated_season' => $calculatedSeason,
        //     'current_date' => date('Y-m-d'),
        //     'current_month' => $currentMonth
        // ]);

        return $currentSeason; // 直接返回當前賽季2024-25
    }

    /**
     * 計算並更新去年的生涯統計數據
     */
    private function calculateLastYearCareerStats($player)
    {
        try {
            // 獲取所有球員的賽季統計數據並按年份排序
            $stats = $player->stats()->orderBy('year')->get();

            if ($stats->isEmpty()) {
                // Log::info('球員無統計數據，跳過計算去年生涯統計', ['player_id' => $player->id]);
                return;
            }

            // 獲取當前NBA賽季年度（格式如：2024-25）
            $currentSeasonFormat = $this->getCurrentNbaSeason();

            // 記錄球員所有賽季數據，用於調試
            // Log::info('球員所有賽季數據', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'all_seasons' => $stats->pluck('year')->toArray()
            // ]);

            // 檢查球員是否有當前賽季的數據
            $hasCurrentSeason = $stats->contains(function ($stat) use ($currentSeasonFormat) {
                return $stat->year === $currentSeasonFormat;
            });

            // 如果沒有當前賽季的數據，不計算去年的統計數據
            if (!$hasCurrentSeason) {
                // Log::info('球員無當前賽季數據，跳過計算去年生涯統計', ['player_id' => $player->id, 'current_season' => $currentSeasonFormat]);
                return;
            }

            // 過濾掉當前賽季的數據，只用過去賽季的數據計算去年的生涯統計
            $previousSeasonStats = $stats->filter(function ($stat) use ($currentSeasonFormat) {
                return $stat->year !== $currentSeasonFormat;
            });

            if ($previousSeasonStats->isEmpty()) {
                // Log::info('球員無過往賽季數據，跳過計算去年生涯統計', ['player_id' => $player->id]);
                return;
            }

            // 記錄詳細的賽季數據
            // Log::info('球員去年生涯統計計算的數據列表', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'total_seasons' => $previousSeasonStats->count(),
            //     'seasons_used' => $previousSeasonStats->pluck('year')->toArray(),
            //     'seasons_data' => $previousSeasonStats->map(function ($stat) {
            //         return [
            //             'year' => $stat->year,
            //             'team' => $stat->team,
            //             'g' => $stat->g,
            //             'fgm' => $stat->fgm,
            //             'fga' => $stat->fga,
            //             'fg%' => $stat->{'fg%'},
            //             'ftm' => $stat->ftm,
            //             'fta' => $stat->fta,
            //             'ft%' => $stat->{'ft%'},
            //             '3pm' => $stat->{'3pm'},
            //             'rpg' => $stat->rpg,
            //             'apg' => $stat->apg,
            //             'stl' => $stat->stl,
            //             'blk' => $stat->blk,
            //             'pts' => $stat->pts,
            //             'ppg' => $stat->ppg
            //         ];
            //     })->toArray()
            // ]);

            // 按照Career計算公式計算去年的生涯統計
            // G=SUM(G)
            // FG%=SUM(FGM)/SUM(FGA)
            // FT%=SUM(FTM)/SUM(FTA)
            // 3PM=SUM(3PM)
            // RPG=SUMPRODUCT(RPG, G) / SUM(G)
            // APG=SUMPRODUCT(APG, G) / SUM(G)
            // STL=SUM(STL)
            // BLK=SUM(BLK)
            // PTS=SUM(PTS)
            // PPG=SUMPRODUCT(PPG, G) / SUM(G)

            // 計算去年的生涯統計
            $lastYearCareerStats = [
                'G' => $previousSeasonStats->sum('g'),
                '3PM' => $previousSeasonStats->sum('3pm'),
                'STL' => $previousSeasonStats->sum('stl'),
                'BLK' => $previousSeasonStats->sum('blk'),
                'PTS' => $previousSeasonStats->sum('pts')
            ];

            // 計算加權平均值 (weighted average)
            $totalGames = $previousSeasonStats->sum('g');

            // RPG = SUMPRODUCT(RPG, G) / SUM(G)
            $rpgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->rpg) && !is_null($stat->g) && $stat->g > 0) {
                    $rpgSum += $stat->rpg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCareerStats['RPG'] = $this->customRound($rpgSum / $totalGames, 1);
            }

            // APG = SUMPRODUCT(APG, G) / SUM(G)
            $apgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->apg) && !is_null($stat->g) && $stat->g > 0) {
                    $apgSum += $stat->apg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCareerStats['APG'] = $this->customRound($apgSum / $totalGames, 1);
            }

            // PPG = SUMPRODUCT(PPG, G) / SUM(G)
            $ppgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->ppg) && !is_null($stat->g) && $stat->g > 0) {
                    $ppgSum += $stat->ppg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCareerStats['PPG'] = $this->customRound($ppgSum / $totalGames, 1);
            }

            // 檢查是否有完整的投籃數據
            $validFgStats = $previousSeasonStats->filter(function ($stat) {
                return !is_null($stat->fgm) && !is_null($stat->fga) && $stat->fga > 0;
            });

            if (!$validFgStats->isEmpty()) {
                $totalFgm = $validFgStats->sum('fgm');
                $totalFga = $validFgStats->sum('fga');
                $lastYearCareerStats['FG%'] = $totalFga > 0 ? round($totalFgm / $totalFga, 3) : null;
            }

            // 檢查是否有完整的罰球數據
            $validFtStats = $previousSeasonStats->filter(function ($stat) {
                return !is_null($stat->ftm) && !is_null($stat->fta) && $stat->fta > 0;
            });

            if (!$validFtStats->isEmpty()) {
                $totalFtm = $validFtStats->sum('ftm');
                $totalFta = $validFtStats->sum('fta');
                $lastYearCareerStats['FT%'] = $totalFta > 0 ? round($totalFtm / $totalFta, 3) : null;
            }

            // 保存去年的生涯統計數據
            $player->last_year_career_stats = $lastYearCareerStats;
            $player->save();

            // 記錄計算結果
            // Log::info('已更新球員去年生涯統計數據', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'stats_count' => $previousSeasonStats->count(),
            //     'calculated_stats' => $lastYearCareerStats
            // ]);
        } catch (\Exception $e) {
            Log::error('計算去年生涯統計數據失敗', [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 計算並更新去年的大學生涯統計數據
     */
    private function calculateLastYearCollegeCareerStats($player)
    {
        try {
            // 獲取所有球員的大學賽季統計數據
            $stats = $player->college_stats()->get();

            if ($stats->isEmpty()) {
                // Log::info('球員無大學統計數據，跳過計算去年大學生涯統計', ['player_id' => $player->id]);
                return;
            }

            // 獲取當前NBA賽季年度（格式如：2024-25）
            $currentSeasonFormat = $this->getCurrentNbaSeason();

            // 記錄球員所有大學賽季數據，用於調試
            // Log::info('球員所有大學賽季數據', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'all_seasons' => $stats->pluck('year')->toArray()
            // ]);

            // 檢查球員是否有當前賽季的統計數據
            $hasCurrentSeason = $stats->contains(function ($stat) use ($currentSeasonFormat) {
                return $stat->year === $currentSeasonFormat;
            });

            // 如果沒有當前賽季的數據，不計算去年的統計數據
            if (!$hasCurrentSeason) {
                // Log::info('球員無當前大學賽季數據，跳過計算去年大學生涯統計', [
                //     'player_id' => $player->id,
                //     'current_season' => $currentSeasonFormat
                // ]);
                return;
            }

            // 過濾掉當前賽季的數據，只用過去賽季的數據計算去年的生涯統計
            $previousSeasonStats = $stats->filter(function ($stat) use ($currentSeasonFormat) {
                return $stat->year !== $currentSeasonFormat;
            });

            if ($previousSeasonStats->isEmpty()) {
                // Log::info('球員無過往大學賽季數據，跳過計算去年大學生涯統計', ['player_id' => $player->id]);
                return;
            }

            // 記錄詳細的大學賽季數據
            // Log::info('球員去年大學生涯統計計算的數據列表', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'total_seasons' => $previousSeasonStats->count(),
            //     'seasons_used' => $previousSeasonStats->pluck('year')->toArray(),
            //     'seasons_data' => $previousSeasonStats->map(function ($stat) {
            //         return [
            //             'year' => $stat->year,
            //             'team' => $stat->team,
            //             'g' => $stat->g,
            //             'fgm' => $stat->fgm,
            //             'fga' => $stat->fga,
            //             'fg%' => $stat->{'fg%'},
            //             'ftm' => $stat->ftm,
            //             'fta' => $stat->fta,
            //             'ft%' => $stat->{'ft%'},
            //             '3pm' => $stat->{'3pm'},
            //             'rpg' => $stat->rpg,
            //             'apg' => $stat->apg,
            //             'stl' => $stat->stl,
            //             'blk' => $stat->blk,
            //             'pts' => $stat->pts,
            //             'ppg' => $stat->ppg
            //         ];
            //     })->toArray()
            // ]);

            // 按照Career計算公式計算去年的大學生涯統計
            // G=SUM(G)
            // FG%=SUM(FGM)/SUM(FGA)
            // FT%=SUM(FTM)/SUM(FTA)
            // 3PM=SUM(3PM)
            // RPG=SUMPRODUCT(RPG, G) / SUM(G)
            // APG=SUMPRODUCT(APG, G) / SUM(G)
            // STL=SUM(STL)
            // BLK=SUM(BLK)
            // PTS=SUM(PTS)
            // PPG=SUMPRODUCT(PPG, G) / SUM(G)

            // 計算去年的大學生涯統計
            $lastYearCollegeCareerStats = [
                'G' => $previousSeasonStats->sum('g'),
                '3PM' => $previousSeasonStats->sum('3pm'),
                'STL' => $previousSeasonStats->sum('stl'),
                'BLK' => $previousSeasonStats->sum('blk'),
                'PTS' => $previousSeasonStats->sum('pts')
            ];

            // 計算加權平均值 (weighted average)
            $totalGames = $previousSeasonStats->sum('g');

            // RPG = SUMPRODUCT(RPG, G) / SUM(G)
            $rpgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->rpg) && !is_null($stat->g) && $stat->g > 0) {
                    $rpgSum += $stat->rpg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCollegeCareerStats['RPG'] = $this->customRound($rpgSum / $totalGames, 1);
            }

            // APG = SUMPRODUCT(APG, G) / SUM(G)
            $apgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->apg) && !is_null($stat->g) && $stat->g > 0) {
                    $apgSum += $stat->apg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCollegeCareerStats['APG'] = $this->customRound($apgSum / $totalGames, 1);
            }

            // PPG = SUMPRODUCT(PPG, G) / SUM(G)
            $ppgSum = 0;
            foreach ($previousSeasonStats as $stat) {
                if (!is_null($stat->ppg) && !is_null($stat->g) && $stat->g > 0) {
                    $ppgSum += $stat->ppg * $stat->g;
                }
            }
            if ($totalGames > 0) {
                $lastYearCollegeCareerStats['PPG'] = $this->customRound($ppgSum / $totalGames, 1);
            }

            // 檢查是否有完整的投籃數據
            $validFgStats = $previousSeasonStats->filter(function ($stat) {
                return !is_null($stat->fgm) && !is_null($stat->fga) && $stat->fga > 0;
            });

            if (!$validFgStats->isEmpty()) {
                $totalFgm = $validFgStats->sum('fgm');
                $totalFga = $validFgStats->sum('fga');
                $lastYearCollegeCareerStats['FG%'] = $totalFga > 0 ? round($totalFgm / $totalFga, 3) : null;
            }

            // 檢查是否有完整的罰球數據
            $validFtStats = $previousSeasonStats->filter(function ($stat) {
                return !is_null($stat->ftm) && !is_null($stat->fta) && $stat->fta > 0;
            });

            if (!$validFtStats->isEmpty()) {
                $totalFtm = $validFtStats->sum('ftm');
                $totalFta = $validFtStats->sum('fta');
                $lastYearCollegeCareerStats['FT%'] = $totalFta > 0 ? round($totalFtm / $totalFta, 3) : null;
            }

            // 保存去年的大學生涯統計數據
            // $player->last_year_college_career_stats = $lastYearCollegeCareerStats;
            $player->save();

            // 記錄計算結果
            // Log::info('已更新球員去年大學生涯統計數據', [
            //     'player_id' => $player->id,
            //     'player_name' => $player->player,
            //     'current_season' => $currentSeasonFormat,
            //     'stats_count' => $previousSeasonStats->count(),
            //     'calculated_stats' => $lastYearCollegeCareerStats
            // ]);
        } catch (\Exception $e) {
            Log::error('計算去年大學生涯統計數據失敗', [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Custom rounding function that ensures proper rounding behavior
     * 
     * @param float $value The value to round
     * @param int $precision The number of decimal places to round to
     * @return float The rounded value
     */
    private function customRound($value, $precision)
    {
        $mult = pow(10, $precision);
        return ceil($value * $mult) / $mult;
    }
}
