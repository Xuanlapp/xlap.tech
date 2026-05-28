<?php

namespace App\Http\Livewire\Player;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Player;
use App\Models\Panini_player;
use App\Models\Source_player;
use App\Models\Matching_list;
use App\Models\Team;

class Show extends Component
{
    use WithPagination;
    public function render()
    {
        $players = Player::paginate($perPage = 100);
        return view('livewire.player.show', ['players' => $players]);
    }

    public function downloadData()
    {
        foreach (Player::paginate($perPage = 100) as $value) {
            $player_info = $this->loadApiData($value->player_id);
            Player::where('id', $value->id)->update($player_info);
        }
    }

    public function downloadSingle($player_id)
    {
        $player_info = $this->loadApiData($player_id);
        //dd($player_info);
        Player::where('player_id', $player_id)->update($player_info);
    }

    public function nameMatch()
    {
        $players = Player::all();
        foreach ($players as $player) {
            $name_match = null;
            $full_name = $player->first_name . " " . $player->last_name;
            if ($full_name === $player->panini_full_name) {
                $name_match = true;
            } else {
                $name_match = false;
            }
            $data['name_match'] = $name_match;
            $player->update($data);
            // dd('Done!');
        }
    }

    public function get_panini_info()
    {
        $mlb_players = Player::where('panini_full_name', null)->get();
        // $mlb_players = Player::all();
        // dd($mlb_players);
        foreach ($mlb_players as $mlb_player) {
            $panini = Panini_player::where('panini_player_id', $mlb_player->panini_id)->get();
            // dd($panini[0]);
            $data['panini_full_name'] = $panini[0]->panini_player_name;
            $data['panini_team'] = $panini[0]->panini_team;
            Player::where('id', $mlb_player->id)->update($data);
        }
    }

    public function team_teams_match()
    {
        foreach (Player::all() as $player) {
            if ($player->team_match == true) {
                $player->any_team_match = true;
                $player->save();
            }
        }
    }

    public function match_last_name()
    {
        foreach (Player::all() as $player) {
            $panini_last_name_arr = explode(" ", $player->panini_full_name);
            $last_name = array();
            foreach ($panini_last_name_arr as $key => $value) {
                if ($key != 0) {
                    $last_name[] = $value;
                }
            }
            $match = false;
            $panini_last_name = implode(" ", $last_name);
            if ($panini_last_name == $player->last_name) {
                $match = true;
            }
            $data['last_name_match'] = $match;
            Player::where('id', $player->id)->update($data);
        }
    }

    public function matching_full_name()
    {
        $panini_players = Panini_player::all();
        foreach ($panini_players as $panini_player) {
            // $source_players = Source_player::where('source_full_name', 'like', '%' . "Zach Daniels" . '%')->get();
            $source_players = Source_player::where('source_full_name', 'like', '%' . $panini_player->panini_player_name . '%')->get();
            dd($source_players);
            if ($source_players->count()) {
                foreach ($source_players as $source_player) {
                    if ($source_player->count_duplicated > 1) {
                        $data['panini_player_id'] = $panini_player->panini_player_id;
                        $data['mlb_player_id'] = $source_player->mlb_player_id;
                        Matching_list::create($data);
                    }
                }
            }
        }
    }

    public function teamMatch()
    {
        $players = Player::all();
        foreach ($players as $player) {
            $team_match = ($player->panini_team === $player->mlb_latest_team) ? True : false;
            $data['team_match'] = $team_match;
            $player->update($data);
            // dd("done!");
        }
    }

    public function getLatestTeam()
    {
        $players = Player::all();
        foreach ($players as $key => $player) {
            $id = $player->id;
            $data['mlb_latest_team'] = $this->getTheLatestTeam($id);
            Player::where('id', $id)->update($data);
        }
    }

    private function getTheLatestTeam($id)
    {
        $player = Player::where('id', $id)->first();
        $latestTeam = "";
        if ($player['stats'] != []) {
            $stats = json_decode($player['stats']);
            foreach ($stats as $stat) {
                if (property_exists($stat, "fullTeamName")) {
                    $latestTeam = $stat->fullTeamName[0];
                }
            }
        }
        return $latestTeam;
    }

    public function loadApiData($player_id)
    {
        $url = "https://statsapi.mlb.com/api/v1/people/" . $player_id . "?hydrate=team,stats(type=[yearByYear,careerRegularSeason,availableStats](team(league)),leagueListId=mlb_hist)&site=en";

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);

        // $player_info = json_decode(file_get_contents($url, true));
        $player_info = json_decode($query);

        // dd($player_info);
        if (property_exists($player_info, 'people')) {
            $player['first_name'] = $player_info->people[0]->firstName;
            $player['last_name'] = $player_info->people[0]->lastName;
            $player['mid_name'] = (property_exists($player_info->people[0], "middleName") ? $player_info->people[0]->middleName : "");
            $player['birth_date'] = (property_exists($player_info->people[0], "birthDate") ? $player_info->people[0]->birthDate : 0);
            $player['birth_city'] = (property_exists($player_info->people[0], "birthCity") ? $player_info->people[0]->birthCity : 0);
            $player['birth_country'] = (property_exists($player_info->people[0], "birthCountry") ? $player_info->people[0]->birthCountry : 0);
            $player['height'] = (property_exists($player_info->people[0], "height") ? $player_info->people[0]->height : 0);
            $player['weight'] = (property_exists($player_info->people[0], "weight") ? $player_info->people[0]->weight : 0);
            $player['active'] = $player_info->people[0]->active;
            if (property_exists($player_info->people[0]->stats[0], "group")) {
                $player['stats'] = $this->handleStatData($player_info->people[0]->stats);
            } else {
                $player['stats'] = array();
            }
            if (property_exists($player_info->people[0]->stats[0], "group")) {
                $player['career_stat'] = json_encode($player_info->people[0]->stats[1]->splits[0]->stat);
            } else {
                $player['career_stat'] = array();
            }
        } else {
            return;
        }
        // dd($player);
        return $player;
    }

    private function handleStatData($data)
    {
        $pitchingStatsKeyArr = array(
            "wins" => "W",
            "losses" => "L",
            "era" => "ERA",
            "gamesPlayed" => "G",
            "gamesStarted" => "GS",
            "saves" => "SV",
            "inningsPitched" => "IP",
            "hits" => "H",
            "baseOnBalls" => "BB",
            "strikeOuts" => "K",
            "whip" => "WHIP"
        );
        $hittingStatsKeyArr = array(
            "gamesPlayed" => "G",
            "atBats" => "AB",
            "runs" => "R",
            "hits" => "H",
            "doubles" => "2B",
            "triples" => "3B",
            "homeRuns" => "HR",
            "rbi" => "RBI",
            "stolenBases" => "SB",
            "obp" => "OBP",
            "slg" => "SLG",
            "avg" => "AVG"
        );
        $position = $data[0]->group->displayName;
        $splits = $data[0]->splits;
        $career = $data[1]->splits[0]->stat;

        $newStatArr = array();
        $i = 0;
        foreach ($splits as $key => $val) {
            $newStatArr[$i]['code'] = 0;
            $newStatArr[$i]['position'] = $position;
            $newStatArr[$i]['season'] = $val->season;
            $newStatArr[$i]['stat'] = $val->stat;
            if (property_exists($val, "team")) {
                $newStatArr[$i]['fullTeamName'][] = $val->team->name;
                $team = $val->team->abbreviation;
            } else {
                $team = "";
            }
            if ($i != 0) {
                if ($newStatArr[$i]['season'] == $newStatArr[$i - 1]['season']) {
                    $newStatArr[$i - 1]['code'] = 1;
                    if (property_exists($val, "team")) {
                        $newStatArr[$i - 1]['team'][] = $team;
                        $newStatArr[$i]['team'] = $newStatArr[$i - 1]['team'];
                    } else {
                        $newStatArr[$i]['team'] = $newStatArr[$i - 1]['team'];
                    }
                } else {
                    $newStatArr[$i]['team'][] = $team;
                }
            } else {
                $newStatArr[$i]['team'][] = $team;
            }
            $i++;
        }
        // return $newStatArr;
        $finalStats = array();
        foreach ($newStatArr as $key => $value) {
            if ($value['code'] == 0) {
                $finalStats[] = $value;
            }
        }
        return $finalStats;
    }

    public function get_team_name_from_api()
    {
        for ($i = 240; $i <= 249; $i++) {
            $url = "https://statsapi.mlb.com/api/v1/teams/" . $i;
            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
            $query = curl_exec($curl_handle);
            curl_close($curl_handle);
            $team_info = json_decode($query);
            // dd($team_info);
            $data['name'] = $team_info->teams[0]->name;
            $data['abbreviation'] = $team_info->teams[0]->abbreviation;

            Team::create($data);
        }
    }

    public function compare_all_team_match()
    {
        foreach (Player::all() as $player) {
            $match = false;
            foreach (json_decode($player->teams_played) as $team) {
                if ($team == $player->panini_team) {
                    $match = true;
                    break;
                }
            }
            $data['any_team_match'] = $match;
            Player::where('id', $player->id)->update($data);
        }
    }

    public function get_played_teams()
    {
        $players = Player::all();
        foreach ($players as $player) {
            if ($player->stats != "") :
                $data['teams_played'] = $this->handle_get_played_teams($player->id);
                Player::where('id', $player->id)->update($data);
            endif;
        }
    }

    private function handle_get_played_teams($player_id)
    {
        $player = Player::where('id', $player_id)->first();
        // dd($player);
        $stats = json_decode($player->stats);
        $teams = array();
        foreach ($stats as $stat) {
            $i = 0;
            if (property_exists($stat, 'team')) {
                foreach ($stat->team as $team) {
                    print_r($i);
                    $i++;
                    $team = Team::where("abbreviation", $team)->first();
                    if ($team) {
                        $teams[] = $team->name;
                    }
                }
            }
        }
        return array_unique($teams);
    }
}
