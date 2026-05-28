<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class panini_mlb_players_stat extends Model
{
    protected $fillable = [
        'player_id',
        'season',
        'team_abb',
        'team_name',
        'stat',
        'position'
    ];

    private $pitching_stat_arr = [
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
    ];
    private $hitting_stat_arr = [
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
    ];

    public function show_stat()
    {
        $stat[] = $this->season;
        //if (Request::session()->has('team_name_option')) {
        // 如果不存在，則存儲變量
        // $team_name_option = Request::session()->get('team_name_option');
        // } else {
        $team_name_option = false;
        // }

        if ($team_name_option) {
            $stat[] = $this->team_name;
        } else {
            $stat[] = $this->team_abb;
        }
//        dd($this->{$this->position . "_stat_arr"});
        $player_stat = json_decode($this->stat);
        foreach ($this->{$this->position . "_stat_arr"} as $key => $stat_arr) {
            $stat[] = property_exists($player_stat, $key) ? $player_stat->{$key} : "-";
        }
        //        dd($stat);
        return $stat;
    }
}
