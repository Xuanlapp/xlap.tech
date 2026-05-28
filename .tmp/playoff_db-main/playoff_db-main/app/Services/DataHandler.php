<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\panini_mlb_player;
use GuzzleHttp\Exception\RequestException;

class DataHandler
{
    public function checkMlbId($mlb_id, $old_mlb_id)
    {
        // Kiểm tra độ dài của MLB ID
        if (strlen($mlb_id) != 6) {
            return false;
        }

        // Kiểm tra nếu MLB ID khác với ID cũ
        if ($mlb_id === $old_mlb_id) {
            return false;
        }

        // Gọi API lấy thông tin
        $protentialPlayer = $this->loadApiDataMajor($mlb_id);
        $leagueStats = $this->loadApiDataMinor($mlb_id);

        // Kiểm tra nếu không có dữ liệu player hoặc league stats
        if ($protentialPlayer === null || $leagueStats === null) {
            return false;
        }

        // Trả về dữ liệu player và league stats nếu hợp lệ
        return [
            'protentialPlayer' => $protentialPlayer,
            'leagueStats' => $leagueStats,
        ];
    }

    public function changeMlbId($data, $player_id, $mlb_player_id)
    {
        $player = panini_mlb_player::find($player_id);
        if (!$player) {
            throw new \Exception("Player not found.");
        }
        $player->leaguestats()->delete();
        $player->stats()->delete();
        if ($player->active == 1 || $player->active == null) {
//            dd("lap join here");
            $datastat = $this->loadApiDataMajor($mlb_player_id);
            $dataleaguestat = $this->loadApiDataMinor($mlb_player_id);

            // Lưu dữ liệu mới
            $this->saveNewLeagueStats($dataleaguestat, $player, $mlb_player_id);
            $this->saveNewPlayerMajor($datastat, $player, $mlb_player_id);
        } else {
            return false;
        }

        return true;
    }

    //code change
    public function loadApiDataMajor($player_id)
    {
        $url = "https://statsapi.mlb.com/api/v1/people/{$player_id}?hydrate=team,stats(type=[yearByYear,careerRegularSeason,availableStats](team(league)),leagueListId=mlb_hist)&site=en";
        $data = $this->fetchDataFromApi($url);
        if ($data === null || !isset($data->people) || !is_array($data->people) || count($data->people) == 0) {
            return null;
        }
        $player_info = $data->people[0];
        $player = [
            'full_name' => $player_info->fullName,
            'first_name' => $player_info->firstName,
            'last_name' => $player_info->lastName,
            'mid_name' => property_exists($player_info, "middleName") ? $player_info->middleName : "",
            'last_played_date' => property_exists($player_info, "lastPlayedDate") ? $player_info->lastPlayedDate : null,
            'active' => $player_info->active,
            'position_name' => $player_info->primaryPosition->name ?? null,
            'position_abb' => $player_info->primaryPosition->abbreviation ?? null,
            'teams_played' => property_exists($player_info->stats[0], "group") ? $this->handleTeam($player_info->stats[0]->splits) : null,
            'curr_career_stat' => isset($player_info->stats[1]->splits) && isset($player_info->stats[1]->splits[0]->stat)
                ? json_encode($player_info->stats[1]->splits[0]->stat)
                : null,
            'stats' => property_exists($player_info->stats[0], "group") ? $this->handleStatDataMajor($player_info->stats) : [],
        ];
        return $player;
    }

    public function saveNewPlayerMajor($data, $player, $mlb_player_id)
    {
        $player->marked = 4;
        $player->full_name = $data['full_name'];
        $player->first_name = $data['first_name'];
        $player->last_name = $data['last_name'];
        $player->middle_name = $data['mid_name'];
        $player->last_played_date = $data['last_played_date'];
        $player->active = $data['active'];
        $player->mlb_player_id = $mlb_player_id;
        $player->position_name = $data['position_name'];
        $player->position_abb = $data['position_abb'];
        $player->teams_played = $data['teams_played'] ?? null;
        $player->career_stat = $data['curr_career_stat'] ?? null;

        foreach ($data['stats'] as $stat) {
            $statData = [
                'position' => $stat['position'],
                'season' => $stat['season'],
                'team_abb' => implode('/', $stat['team']),
                'team_name' => implode('/', $stat['teamFullName']),
                'stat' => json_encode($stat['stat']),
            ];

            $match = false;
            foreach ($player->stats as $old_stat) {
                if ($old_stat->season == $stat['season']) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                $player->savePlayerStat($statData);
            }
        }

        $player->flag = 1;
        $player->save();
    }


    public function loadApiDataMinor($player_id)
    {
        $url = "https://statsapi.mlb.com/api/v1/people/{$player_id}/stats?stats=yearByYear&gameType=R&leagueListId=milb_all&group=pitching&hydrate=team(league)&language=en";
        $retryCount = 0;
        $maxRetries = 5;
        $data = null;

        while ($retryCount < $maxRetries) {
            try {
                $client = $this->createHttpClient();
                $response = $client->get($url);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $data = json_decode($response->getBody(), true);
                    break;
                } else {
                    throw new RequestException('Unsuccessful response');
                }
            } catch (RequestException $e) {
                $retryCount++;
                sleep(2);
            }
        }

        if ($data === null) {
            return null;
        }

        $player_info = $data;
        if ($player_info) {
            $player['stats'] = $this->handleStatDataMinor($data['stats']);
            return $player;
        }

        return null;
    }

    public function saveNewLeagueStats($data, $player, $mlb_player_id)
    {
        $player->mlb_player_id = $mlb_player_id;

        if ($data === null || !isset($data['stats'])) {
            return;
        }
        foreach ($data['stats'] as $stat) {
            $statData = [
                'season' => $stat['season'],
                'position' => $stat['position'],
                'level' => $stat['level'],
                'stat' => $stat['stat'],
            ];

            $match = false;
            foreach ($player->leaguestats as $old_stat) {
                if ($old_stat->season == $stat['season']) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                $player->savePlayerLeagueStat($statData);
            }
        }
        $player->flag = 1;
        $player->save();
    }

    private
    function handleStatDataMajor($data)
    {
        $position = $data[0]->group->displayName;
        $splits = $data[0]->splits;

        $newStatArr = [];
        $i = 0;
        foreach ($splits as $val) {
            $statEntry = [
                'code' => 0,
                'position' => $position,
                'season' => $val->season,
                'stat' => $val->stat,
                'team' => [],
                'teamFullName' => [],
            ];

            if (property_exists($val, "team")) {
                $teamAbbreviation = $val->team->abbreviation;
                $teamFullName = $val->team->name;
            } else {
                $teamAbbreviation = "";
                $teamFullName = "";
            }

            if ($i > 0 && $statEntry['season'] == $newStatArr[$i - 1]['season']) {
                $newStatArr[$i - 1]['code'] = 1;
                $newStatArr[$i - 1]['team'][] = $teamAbbreviation;
                $newStatArr[$i - 1]['teamFullName'][] = $teamFullName;
                $statEntry['team'] = $newStatArr[$i - 1]['team'];
                $statEntry['teamFullName'] = $newStatArr[$i - 1]['teamFullName'];
            } else {
                $statEntry['team'][] = $teamAbbreviation;
                $statEntry['teamFullName'][] = $teamFullName;
            }

            $newStatArr[] = $statEntry;
            $i++;
        }

        $finalStats = array_filter($newStatArr, function ($value) {
            return $value['code'] == 0;
        });
//        dd($finalStats);
        return $finalStats;
    }

    private
    function handleStatDataMinor($splits)
    {

        $seasonsProcessed = [];
        $minorsData = [];
        $allData = [];
        $minorsSeasons = [];
        foreach ($splits as $split) {
            if (isset($split['splits']) && is_array($split['splits'])) {
                foreach ($split['splits'] as $innerSplit) {
                    $abbreviation = $innerSplit['sport']['abbreviation'] ?? '';
                    $season = $innerSplit['season'];
                    $position = $split['group']['displayName'] ?? '';
                    $stats = $innerSplit['stat'] ?? [];
                    if ($abbreviation === 'Minors') {
                        $minorsSeasons[] = $season;
                        if (in_array($season, $seasonsProcessed)) {
                            continue;
                        }
                        $levels = [];
                        foreach ($split['splits'] as $checkSplit) {
                            if ($checkSplit['season'] == $season) {
                                $checkAbbreviation = $checkSplit['sport']['abbreviation'];
                                if ($checkAbbreviation === 'Minors') {
                                    continue;
                                }
                                if (in_array($checkAbbreviation, ['AA', 'AAA'])) {
                                    $levels[] = $checkAbbreviation;
                                } else {
                                    $levels[] = 'Class A';
                                }
                            }
                        }
                        $levelString = implode('/', array_unique($levels));
                        if (strpos($levelString, 'Class A/') === 0) {
                            $levelString = str_replace('Class A/', 'A/', $levelString);
                        }
                        $minorsData[$season] = [
                            'level' => $levelString,
                            'stat' => json_encode($stats),
                            'position' => $position,
                            'season' => $season,
                        ];
                        $seasonsProcessed[] = $season;
                    } else {
                        if (!in_array($season, $minorsSeasons)) {
                            if (!in_array($abbreviation, ['AA', 'AAA'])) {
                                $abbreviation = 'Class A';
                            }
                            $allData[] = [
                                'level' => $abbreviation,
                                'stat' => json_encode($stats),
                                'position' => $position,
                                'season' => $season,
                            ];
                        }
                    }
                }
            }
        }
        $filteredStats = array_merge(array_values($minorsData), $allData);
        usort($filteredStats, function ($a, $b) {
            return $a['season'] <=> $b['season'];
        });

        if (empty($filteredStats)) {
            return null;
        }

        return $filteredStats;
    }

    private function fetchDataFromApi($url)
    {
        $retryCount = 0;
        $maxRetries = 5;

        while ($retryCount < $maxRetries) {
            try {
                $client = $this->createHttpClient();
                $response = $client->get($url);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return json_decode($response->getBody());
                }
                throw new \Exception('Unsuccessful response');
            } catch (\Exception $e) {
                $retryCount++;
                sleep(2);
            }
        }

        return null;
    }

    private function createHttpClient(): Client
    {
        return new Client([
            'verify' => false,
            'timeout' => 10,
        ]);
    }

    private
    function handleTeam($data)
    {
        $teams = [];
        foreach ($data as $val) {
            if (array_key_exists("team", (array)$val)) {
                $teams[] = $val->team->name;
            }
        }

        $sequential_index = [];
        $index = 0;
        foreach (array_unique($teams) as $team) {
            $sequential_index[$index] = $team;
            $index++;
        }
        // Log::channel('mylog')->info(array_unique($sequential_index));
        return json_encode(array_unique($sequential_index));
    }

}
