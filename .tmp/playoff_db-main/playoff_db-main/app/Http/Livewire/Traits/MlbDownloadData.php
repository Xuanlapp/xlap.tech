<?php

namespace App\Http\Livewire\Traits;

use GuzzleHttp\Client;
use App\Models\PaniniTeam;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

trait MlbDownloadData
{
    private function getPlayerFullname($player_id)
    {
        $url = "https://statsapi.mlb.com/api/v1/people/" . $player_id . "?hydrate=team,stats(type=[yearByYear,careerRegularSeason,availableStats](team(league)),leagueListId=mlb_hist)&site=en";

        $retryCount = 0;
        $maxRetries = 5;

        while ($retryCount < $maxRetries) {
            try {
                $client = new Client();
                $response = $client->get($url);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $data = json_decode($response->getBody());
                    // Do something with the successful response

                    break; // Break out of the while loop if successful
                } else {
                    // Handle unsuccessful response
                    throw new RequestException('Unsuccessful response');
                    $data = null;
                }
            } catch (RequestException $e) {
                // Handle error when there is a network error or timeout
                // $data = null;
                $retryCount++;
            }
        }
        if ($data == null) {
            return null;
        } else {
            // Log::channel('mylog')->info($data);
            $player_info = $data;
            $player = [];
            if (property_exists($player_info, 'people')) {
                $player['full_name'] = $player_info->people[0]->fullName;
                $player['first_name'] = $player_info->people[0]->firstName;
                $player['last_name'] = $player_info->people[0]->lastName;
                $player['middle_name'] = (property_exists($player_info->people[0], "middleName") ? $player_info->people[0]->middleName : "");
                $player['lastplayedate'] = (property_exists($player_info->people[0], "lastPlayedDate") ? $player_info->people[0]->lastPlayedDate : 0);
                return $player;
            } else {
                return null;
            }
        }
    }

    private function compare_all_team_match($teams_played, $panini_team)
    {
        // dd(json_decode($teams_played));
        $match = false;
        if ($teams_played !== NULL) {
            foreach (json_decode($teams_played) as $team) {
                if ($team === $panini_team) {
                    // dd($team);
                    $match = true;
                    break;
                }
            }
        }
        return $match;
    }

    private function updateData($player)
    {
        // dd($player);
        $player_info = $this->loadApiData($player->player_id);
        // dd($player_info);
        $new_career_stat = $player_info['curr_career_stat'];

        /**
         * AFTER GETTING PLAYER INFO! FIRST OF ALL, COMPARE THE CAREER STAT FIRST 
         **/
        if ($this->compareCareerStat($player['curr_career_stat'], $new_career_stat)) {
            //IF RETURN TRUE
            return false;
        } else {
            // return true;
            /** IF RETURN FALSE, DO THE DOWNLOAD STEP **/
            //ASSIGN CURRENT STAT TO ACHIEVE STAT FIELD
            $player->achi_career_stat = $player->curr_career_stat;

            $player->curr_career_stat = $new_career_stat;
            $player->stats = $player_info['stats'];
            $player->teams_played = $player_info['teams_played'];
            $player->save();
            return true;
        }
    }
}
