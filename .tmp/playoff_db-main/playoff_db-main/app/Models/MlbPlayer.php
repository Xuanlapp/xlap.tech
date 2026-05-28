<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class MlbPlayer extends Model
{

    protected $fillable = ['panini_id', 'panini_full_name', 'panini_team', 'status', 'team_id', 'name_match', 'team_match', 'stats', 'curr_career_stat', 'first_name', 'last_name', 'mid_name', 'birth_date', 'birth_city', 'birth_country', 'height', 'weight', 'active', 'teams_played'];

    public function team()
    {
        return $this->belongsTo(MlbPaniniTeam::class, 'team_id');
    }

    private $pitching_stat_arr = [
        "wins" => "W", "losses" => "L", "era" => "ERA", "gamesPlayed" => "G", "gamesStarted" => "GS", "saves" => "SV", "inningsPitched" => "IP", "hits" => "H", "baseOnBalls" => "BB", "strikeOuts" => "K", "whip" => "WHIP"
    ];
    private $hitting_stat_arr = [
        "gamesPlayed" => "G", "atBats" => "AB", "runs" => "R", "hits" => "H", "doubles" => "2B", "triples" => "3B", "homeRuns" => "HR", "rbi" => "RBI", "stolenBases" => "SB", "obp" => "OBP", "slg" => "SLG", "avg" => "AVG"
    ];
    public function player_img_url()
    {
        return "https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_639,q_auto:best/v1/people/" . $this->player_id . "/headshot/67/current";
    }

    public function played_teams()
    {
        return implode(' | ', (array)json_decode($this->teams_played));
    }

    public function get_stat_title()
    {

        // Log::channel('mylog')->info($this->panini_id);
        if ($this->check_position() == "pitching") {
            $stat_title_arr = $this->pitching_stat_arr;
        }

        if ($this->check_position() == "hitting") {
            $stat_title_arr = $this->hitting_stat_arr;
        }
        // Log::channel('mylog')->info($this->curr_career_stat);

        foreach ($stat_title_arr as $stat_arr) {
            $output_arr[] = $stat_arr;
        }
        return $output_arr;
    }

    private function check_position()
    {
        if (gettype($this->stats) == "array") {
            $stats = $this->stats;
            return $stats[0]['position'];
        } else {
            $stats = (array)json_decode($this->stats);
            return $stats[0]->position;
        }
    }

    public function get_career_stat()
    {
        $career_stat = (array)json_decode($this->curr_career_stat);
        $output_career_stat[] = "Career";
        $output_career_stat[] = "";
        if ($this->check_position() == 'pitching') {
            foreach ($this->pitching_stat_arr as $key => $stat_arr) {
                $output_career_stat[] = $career_stat[$key];
            }
        }
        if ($this->check_position() == 'hitting') {
            foreach ($this->hitting_stat_arr as $key => $stat_arr) {
                $output_career_stat[] = $career_stat[$key];
            }
        }

        return $output_career_stat;
    }

    function convertArrayToObject($data)
    {
        if (is_array($data)) {
            return array_map([__CLASS__, __FUNCTION__], $data);
        } elseif (is_object($data)) {
            return array_map([__CLASS__, __FUNCTION__], get_object_vars($data));
        } else {
            return $data;
        }
    }

    public function get_year_stats($yearCount)
    {
        if (gettype($this->stats) == "array") {
            $stats = $this->stats;
        } else {
            // dd((array)json_decode($this->stats));
            $stats = (array)json_decode($this->stats);
        }

        $stats = $this->convertArrayToObject($stats);

        // dd($stats);
        if ($yearCount == 0) {
            $newStatArr = $stats;
        }

        if (count($stats) >= $yearCount) {
            for ($i = $yearCount; $i > 0; $i--) {
                $newStatArr[] = $stats[count($stats) - $i];
            }
        } else {
            $newStatArr = $stats;
        }
        // dd($newStatArr);
        if ($this->check_position() == "pitching") {
            $stat_arr = $this->pitching_stat_arr;
        }

        if ($this->check_position() == "hitting") {
            $stat_arr = $this->hitting_stat_arr;
        }

        $output_stats = array();

        foreach ($newStatArr as $stat) {
            $output = array();
            $output[] = $stat['season'];
            $output[] = implode("/", $stat['team']);

            // dd((array)$stat->stat);
            foreach ($stat_arr as $key => $item) {
                $turn_arr = (array)$stat['stat'];
                if (array_key_exists($key, $turn_arr)) {
                    $output[] = $turn_arr[$key];
                } else {
                    $output[] = 0;
                }
            }
            $output_stats[] = $output;
        }
        // dd($output_stats);
        return $output_stats;
    }

    public function show_stats()
    {
        if ($this->stats !== "[]") {
            $output_final_stat['title'] = $this->get_stat_title();
            $output_final_stat['career'] = $this->get_career_stat();
            $output_final_stat['oneYear'] = $this->get_year_stats(1);
            return $output_final_stat;
        }
        return;
    }
}
