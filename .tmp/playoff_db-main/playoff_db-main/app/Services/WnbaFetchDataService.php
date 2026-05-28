<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class WnbaFetchDataService
{
    use LivewireAlert;

    public function saveData($player, $saveMode)
    {

        $wnbaStatResult = $this->SavedataStat($player, $saveMode);

        if ($wnbaStatResult) {
            return true;
        } else {
            $player->marked = 4;
            $player->save();
            return false;
        }
    }


    public function SavedataStat($player, $saveMode)
    {

        $data = $this->handleStats($player->wnba_player_id);

        $careerDatas = $this->handleCareer($player->wnba_player_id);

        if ($data) {
            if ($saveMode == "update") {
                $playerAllStats = $player->stats;
                if ($playerAllStats->count() > 0) {
                    $match = false;
                    foreach ($playerAllStats as $previous_stat) {
                        if ($previous_stat->year == $data['stats']['year'] && $previous_stat->career == 0) {
                            $match = true;
                            break;
                        }
                        if ($previous_stat->year < $data['stats']['year'] && $previous_stat->career == 0) {
                            $previous_stat->update([
                                'year' => $data['stats']['year'] ?? null,
                                'team' => $data['stats']['team'] ?? null,
                                'g' => $data['stats']['g'] ?? null,
                                'fg%' => $data['stats']['fg%'] ?? null,
                                'ft%' => $data['stats']['ft%'] ?? null,
                                '3pm' => $data['stats']['3pm'] ?? null,
                                'rpg' => $data['stats']['rpg'] ?? null,
                                'apg' => $data['stats']['apg'] ?? null,
                                'stl' => $data['stats']['stl'] ?? null,
                                'blk' => $data['stats']['blk'] ?? null,
                                'pts' => $data['stats']['pts'] ?? null,
                                'ppg' => $data['stats']['ppg'] ?? null,
                                'updated_at' => now(),
                            ]);
                            $match = true;
                            break;
                        }
                    }
                    if ($match == false) {
                        $statsave = $data['stats'];
                        $statData = [
                            'player_id' => $player->id,
                            'year' => $statsave['year'] ?? null,
                            'team' => $statsave['team'] ?? null,
                            'g' => $statsave['g'] ?? null,
                            'fg%' => $statsave['fg%'] ?? null,
                            'ft%' => $statsave['ft%'] ?? null,
                            '3pm' => $statsave['3pm'] ?? null,
                            'rpg' => $statsave['rpg'] ?? null,
                            'apg' => $statsave['apg'] ?? null,
                            'stl' => $statsave['stl'] ?? null,
                            'blk' => $statsave['blk'] ?? null,
                            'pts' => $statsave['pts'] ?? null,
                            'ppg' => $statsave['ppg'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $player->savePlayerStat($statData);
                    }
                } else {
                    $statsave = $data['stats'];
                    $statData = [
                        'player_id' => $player->id,
                        'year' => $statsave['year'] ?? null,
                        'team' => $statsave['team'] ?? null,
                        'g' => $statsave['g'] ?? null,
                        'fg%' => $statsave['fg%'] ?? null,
                        'ft%' => $statsave['ft%'] ?? null,
                        '3pm' => $statsave['3pm'] ?? null,
                        'rpg' => $statsave['rpg'] ?? null,
                        'apg' => $statsave['apg'] ?? null,
                        'stl' => $statsave['stl'] ?? null,
                        'blk' => $statsave['blk'] ?? null,
                        'pts' => $statsave['pts'] ?? null,
                        'ppg' => $statsave['ppg'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $player->savePlayerStat($statData);
                }
                $careermath = false;
                $playerAllCarerr = $player->stats()->where('career', 1)->get();

                if ($playerAllCarerr->count() > 0) {
                    foreach ($playerAllCarerr as $previous_career) {
                        $previous_career_seasons = (int)preg_replace('/[^0-9]/', '', $previous_career->team);
                        $new_career_seasons = (int)preg_replace('/[^0-9]/', '', $careerDatas['career']['team']);
                        if ($previous_career_seasons == $new_career_seasons) {
                            $careermath = true;
                            break;
                        }
                        if ($previous_career_seasons < $new_career_seasons) {
                            $previous_career->update([
                                'year' => $careerDatas['career']['year'] ?? null,
                                'team' => $careerDatas['career']['team'] ?? null,
                                'g' => $careerDatas['career']['g'] ?? null,
                                'fg%' => $careerDatas['career']['fg%'] ?? null,
                                'ft%' => $careerDatas['career']['ft%'] ?? null,
                                '3pm' => $careerDatas['career']['3pm'] ?? null,
                                'rpg' => $careerDatas['career']['rpg'] ?? null,
                                'apg' => $careerDatas['career']['apg'] ?? null,
                                'stl' => $careerDatas['career']['stl'] ?? null,
                                'blk' => $careerDatas['career']['blk'] ?? null,
                                'pts' => $careerDatas['career']['pts'] ?? null,
                                'ppg' => $careerDatas['career']['ppg'] ?? null,
                                'updated_at' => now(),
                                'career' => 1,
                            ]);
                            $careermath = true;
                        }
                    }
                    if ($careermath == false) {
                        $careerSave = $careerDatas['career'];
                        $careerDataToSave = [
                            'player_id' => $player->id,
                            'year' => $careerSave['year'] ?? null,
                            'team' => $careerSave['team'] ?? null,
                            'g' => $careerSave['g'] ?? null,
                            'fg%' => $careerSave['fg%'] ?? null,
                            'ft%' => $careerSave['ft%'] ?? null,
                            '3pm' => $careerSave['3pm'] ?? null,
                            'rpg' => $careerSave['rpg'] ?? null,
                            'apg' => $careerSave['apg'] ?? null,
                            'stl' => $careerSave['stl'] ?? null,
                            'blk' => $careerSave['blk'] ?? null,
                            'pts' => $careerSave['pts'] ?? null,
                            'ppg' => $careerSave['ppg'] ?? null,
                            'career' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $player->savePlayerStat($careerDataToSave);
                    }
                } else {
                    $careerData = $careerDatas['career'];
                    $careerDatas = [
                        'player_id' => $player->id,
                        'year' => $careerData['year'] ?? null,
                        'team' => $careerData['team'] ?? null,
                        'g' => $careerData['g'] ?? null,
                        'fg%' => $careerData['fg%'] ?? null,
                        'ft%' => $careerData['ft%'] ?? null,
                        '3pm' => $careerData['3pm'] ?? null,
                        'rpg' => $careerData['rpg'] ?? null,
                        'apg' => $careerData['apg'] ?? null,
                        'stl' => $careerData['stl'] ?? null,
                        'blk' => $careerData['blk'] ?? null,
                        'pts' => $careerData['pts'] ?? null,
                        'ppg' => $careerData['ppg'] ?? null,
                        'extra' => $careerData['extra'] ?? null,
                        'career' => 1, // Set 1 for career stats
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $player->savePlayerStat($careerDatas);
                }
            } else {
                if ($player->stats()->count() > 0) {
                    $player->stats()->delete();
                }
                $careerData = $careerDatas['career'];
                $careerDatas = [
                    'player_id' => $player->id,
                    'year' => $careerData['year'] ?? null,
                    'team' => $careerData['team'] ?? null,
                    'g' => $careerData['g'] ?? null,
                    'fg%' => $careerData['fg%'] ?? null,
                    'ft%' => $careerData['ft%'] ?? null,
                    '3pm' => $careerData['3pm'] ?? null,
                    'rpg' => $careerData['rpg'] ?? null,
                    'apg' => $careerData['apg'] ?? null,
                    'stl' => $careerData['stl'] ?? null,
                    'blk' => $careerData['blk'] ?? null,
                    'pts' => $careerData['pts'] ?? null,
                    'ppg' => $careerData['ppg'] ?? null,
                    'extra' => $careerData['extra'] ?? null,
                    'career' => 1, // Set 1 for career stats
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $player->savePlayerStat($careerDatas);
                $statsave = $data['stats'];
                $statData = [
                    'player_id' => $player->id,
                    'year' => $statsave['year'] ?? null,
                    'team' => $statsave['team'] ?? null,
                    'g' => $statsave['g'] ?? null,
                    'fg%' => $statsave['fg%'] ?? null,
                    'ft%' => $statsave['ft%'] ?? null,
                    '3pm' => $statsave['3pm'] ?? null,
                    'rpg' => $statsave['rpg'] ?? null,
                    'apg' => $statsave['apg'] ?? null,
                    'stl' => $statsave['stl'] ?? null,
                    'blk' => $statsave['blk'] ?? null,
                    'pts' => $statsave['pts'] ?? null,
                    'ppg' => $statsave['ppg'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $player->savePlayerStat($statData);
            }
            $player->marked = 4;
            $player->save();
            return true;
        } else {
            return false;
        }
    }

    private
    function getPlayerLatestYear($player)
    {
        if ($player->stats()->count()) {
            $full_latest_year = $player->stats->last()->year;
            $latest_year = explode("-", $full_latest_year)[0] + 1;
            $player->latest_season = $latest_year;
            $player->save();
        }
    }

    private function handleCareer($player_id)
    {
        $totalsCareer = ['3pm' => 9, 'stl' => 19, 'blk' => 20, 'pts' => 23];
        $perGameCareer = ['g' => 3, 'fg%' => 8, 'ft%' => 14, 'rpg' => 17, 'apg' => 17, 'ppg' => 23];
        $careerPerGame = $this->loadCareerData($player_id, 'PerGame');
        $careerTotals = $this->loadCareerData($player_id, 'Totals');

        $careerTotalsData = null;
        $careerPerGameData = null;
        $uniqueSeasons = []; // Array to track unique years

        // Process career totals data
        if (isset($careerTotals['resultSets'])) {
            foreach ($careerTotals['resultSets'] as $resultSet) {
                if (isset($resultSet['name']) && $resultSet['name'] === 'CareerTotalsRegularSeason') {
                    $careerTotalsData = $resultSet['rowSet'][0];
                    break;
                }
            }
        }
        if (isset($careerPerGame['resultSets'])) {
            foreach ($careerPerGame['resultSets'] as $resultSet) {
                if (isset($resultSet['name']) && $resultSet['name'] === 'CareerTotalsRegularSeason') {
                    $careerPerGameData = $resultSet['rowSet'][0];
                    break;
                }
            }
        }

        // Initialize `career` array and populate with career totals and per-game data
        $career = [];
        if ($careerTotalsData && $careerPerGameData) {
            foreach ($totalsCareer as $key => $index) {
                $career[$key] = $careerTotalsData[$index];
            }
            foreach ($perGameCareer as $key => $index) {
                $career[$key] = $careerPerGameData[$index];
            }
        }

        // Load and process season totals data to count unique years in SeasonTotalsRegularSeason
        $seasonTotalsData = $this->loadCareerData($player_id, 'Totals');
        if (isset($seasonTotalsData['resultSets'])) {
            foreach ($seasonTotalsData['resultSets'] as $resultSet) {
                if (isset($resultSet['name']) && $resultSet['name'] === 'SeasonTotalsRegularSeason') {
                    foreach ($resultSet['rowSet'] as $seasonData) {
                        $seasonYear = $seasonData[1]; // Assuming index 1 represents the year in the data
                        if (!in_array($seasonYear, $uniqueSeasons)) {
                            $uniqueSeasons[] = $seasonYear;
                        }
                    }
                    break;
                }
            }
        }
        $career['team'] = count($uniqueSeasons) . ' WNBA Seasons';

        $player['career'] = array_reverse($career);
        return $player;
    }

    private function handleStats($player_id)
    {
        $totalsByYear = ['3pm' => 13, 'stl' => 24, 'pts' => 29];
        $perGameByYear = ['year' => 1, 'team' => 3, 'g' => 5, 'fg%' => 12, 'ft%' => 18, 'rpg' => 21, 'apg' => 22, 'blk' => 25, 'ppg' => 29];
        $totalsData = $this->loadApiData($player_id, 'Totals');
        $perGameData = $this->loadApiData($player_id, 'PerGame');
        $seasonTotalsRegular = null;
        $seasonPerGameRegular = null;
        if (isset($totalsData['resultSets'])) {
            foreach ($totalsData['resultSets'] as $resultSet) {
                if (isset($resultSet['name']) && $resultSet['name'] === 'ByYearPlayerDashboard') {
                    $seasonTotalsRegular = $resultSet;
                    break;
                }
            }
        }
        if (isset($perGameData['resultSets'])) {
            foreach ($perGameData['resultSets'] as $resultSet) {
                if (isset($resultSet['name']) && $resultSet['name'] === 'ByYearPlayerDashboard') {
                    $seasonPerGameRegular = $resultSet;
                    break;
                }
            }
        }

        if ($seasonTotalsRegular && count($seasonTotalsRegular['rowSet']) > 0) {
            $years = array_column($seasonTotalsRegular['rowSet'], $perGameByYear['year']);
            array_multisort($years, SORT_DESC, $seasonTotalsRegular['rowSet']);

            $latestYearData = $seasonTotalsRegular['rowSet'][0];
            $latestPerGameData = $seasonPerGameRegular ? $seasonPerGameRegular['rowSet'][0] : null;

            $stats = [];
            foreach ($totalsByYear as $key => $index) {
                $stats[$key] = $latestYearData[$index];
            }
            foreach ($perGameByYear as $key => $index) {
                $stats[$key] = $latestPerGameData ? $latestPerGameData[$index] : $latestYearData[$index];
            }

            if ($latestYearData[$perGameByYear['g']] > 0) {
                $stats['ppg'] = $latestYearData[$totalsByYear['pts']] / $latestYearData[$perGameByYear['g']];
            } else {
                $stats['ppg'] = 0;
            }

            // Store season stats
            $player['stats'] = array_reverse($stats);
            return $player;
        } else {
            return false;
        }
    }

    private function loadApiData($player_id, $perMode)
    {
        $url = "https://stats.wnba.com/stats/playerdashboardbyyearoveryear?MeasureType=Base&PerMode=" . $perMode . "&PlusMinus=N&PaceAdjust=N&Rank=N&LeagueID=10&Season=2024&SeasonType=Regular%20Season&PORound=0&PlayerID=" . $player_id . "&Month=0&OpponentTeamID=0&Period=0&LastNGames=0";
        $retryCount = 0;
        $maxRetries = 3;

        while ($retryCount < $maxRetries) {
            try {
                $headers = [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Connection' => 'keep-alive',
                    'Referer' => 'https://stats.wnba.com/',
                    'Origin' => 'https://stats.wnba.com',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36'
                ];

                $client = new Client([
                    'headers' => $headers,
                ]);
                $response = $client->request('GET', $url, ['timeout' => 5]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return json_decode($response->getBody(), true);
                } else {
                    throw new RequestException('Phản hồi không thành công');
                }
            } catch (RequestException $e) {
                error_log("Lỗi: " . $e->getMessage());
                $retryCount++;
                sleep(2);
            }
        }

        return null;
    }

    private function loadCareerData($player_id, $perMode)
    {
        $url = "https://stats.wnba.com/stats/playercareerstats?LeagueID=10&PerMode=" . $perMode . "&PlayerID=" . $player_id;
        $retryCount = 0;
        $maxRetries = 3;

        while ($retryCount < $maxRetries) {
            try {
                $headers = [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Connection' => 'keep-alive',
                    'Referer' => 'https://stats.wnba.com/',
                    'Origin' => 'https://stats.wnba.com',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36'
                ];

                $client = new Client([
                    'headers' => $headers,
                ]);
                $response = $client->request('GET', $url, ['timeout' => 5]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return json_decode($response->getBody(), true);
                } else {
                    throw new RequestException('Phản hồi không thành công');
                }
            } catch (RequestException $e) {
                error_log("Lỗi: " . $e->getMessage());
                $retryCount++;
                sleep(2);
            }
        }

        return null;
    }
}
