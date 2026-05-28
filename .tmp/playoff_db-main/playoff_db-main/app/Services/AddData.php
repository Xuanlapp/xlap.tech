<?php

namespace App\Services;

use App\Models\panini_mlb_player;
use GuzzleHttp\Client;
use App\Models\panini_mlb_players_league_stat;
use GuzzleHttp\Exception\RequestException;

class AddData
{

    /**
     * checkMlbId
     *
     * @param mixed $mlb_id
     * @return object $protentialPlayer / false
     */
    public function checkMlbId($mlb_id, $old_mlb_id)
    {

        if (strlen($mlb_id) == 6) {
            if ($mlb_id !== $old_mlb_id) {
                $protentialPlayer = $this->loadApiDatas($mlb_id);
                if ($protentialPlayer == null) {
                    return false;
                } else {
                    return $protentialPlayer;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * changeMlbId, what the different is delete all the stats before save
     *
     * @param mixed $data
     * @param mixed $player_id
     * @return void
     */
    public function changeMlbId($data, $player_id, $mlb_player_id)
    {
        // Find the player by player ID
//        dd($player_id);
        $player = panini_mlb_player::find($player_id);
        // Remove all the stat
        $player->leaguestats()->delete();

        if ($player->active == 1) {
            $datas = $this->loadApiDatas($mlb_player_id);
            // this call from Trait MlbDownloadData
            $this->saveNewleaguestats($datas, $player, $mlb_player_id);
        }

    }

    /**
     * saveNewleaguestats
     *
     * @param mixed $data
     * @param mixed $player
     * @param mixed $mlb_player_id
     * @return void
     */

    public function loadApiDatas($player_id)
    {
        $url = "https://statsapi.mlb.com/api/v1/people/" . $player_id . "/stats?stats=yearByYear&gameType=R&leagueListId=milb_all&group=pitching&hydrate=team(league)&language=en";
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
                    // Handle unsuccessful response
                    throw new RequestException('Unsuccessful response');
                }
            } catch (RequestException $e) {
                $retryCount++;
                sleep(2);
            }
        }
        if ($data == null) {
            return null;
        } else {
            $player_info = $data;
            if ($player_info) {
                $player['stats'] = $this->handleStatDatas($data['stats']);

                return $player;
            } else {
                return null;
            }
        }
    }

    private function handleStatDatas($splits)
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

    public function saveNewleaguestats($data, $player, $mlb_player_id)
    {

        $player->mlb_player_id = $mlb_player_id;
        foreach ($data['stats'] as $key => $stat) {
            $data = [];
            $data['season'] = $stat['season'];
            $data['position'] = $stat['position'];
            $data['level'] = $stat['level'];
            $data['stat'] = $stat['stat'];
//            dd($data);
            if ($player->leaguestats->count() == 0) {
                $match = 0;
                foreach ($player->leaguestats as $old_stat) {
                    if ($old_stat->season == $stat['season']) {
                        $match = 1;
                        break;
                    }
                }
                if (!$match) {
                    $player->savePlayerleagueStat($data);
                }
            }
        }
        $player->flag = 1;
        $player->save();
    }

    private function createHttpClient(): Client
    {
        return new Client([
            'verify' => false,
            'timeout' => 10,
        ]);
    }
}
