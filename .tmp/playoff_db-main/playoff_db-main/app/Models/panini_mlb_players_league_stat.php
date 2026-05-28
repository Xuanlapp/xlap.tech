<?php
//
//namespace App\Models;
//
//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
//use Illuminate\Support\Facades\Request;
//class panini_mlb_players_league_stat extends Model
//{
//    use HasFactory;
//    // Định nghĩa tên bảng
////    protected $table = 'panini_mlb_players_league_stat';
//    protected $fillable = [
//        'player_id', 'season', 'level', 'stat', 'position'
//    ];
//    // Các cột có thể gán giá trị hàng loạt
//    protected $guarded = ['id'];
//    protected $casts = [
//        'stat' => 'json',
//    ];
//
//    private $pitching_stat_arr = [
//        "wins" => "W", "losses" => "L", "era" => "ERA", "gamesPlayed" => "G", "gamesStarted" => "GS", "saves" => "SV", "inningsPitched" => "IP", "hits" => "H", "baseOnBalls" => "BB", "strikeOuts" => "K", "whip" => "WHIP"
//    ];
//    private $hitting_stat_arr = [
//        "gamesPlayed" => "G", "atBats" => "AB", "runs" => "R", "hits" => "H", "doubles" => "2B", "triples" => "3B", "homeRuns" => "HR", "rbi" => "RBI", "stolenBases" => "SB", "obp" => "OBP", "slg" => "SLG", "avg" => "AVG"
//    ];
//    public function show_stats()
//    {
//        $stat = [];
//        $stat[] = $this->season;
//        $stat[] = $this->level;
//
//        $player_stat = $this->stat;
//        foreach ($this->{$this->position . "_stat_arr"} as $key => $stat_arr) {
//            $stat[] = property_exists($player_stat, $key) ? $player_stat->{$key} : "-";
//        }
////        $stat_arr = $this->{$this->position . "_stat_arr"};
////
////        foreach ($stat_arr as $key => $label) {
////            $stat[] = isset($player_stat[$key]) ? $player_stat[$key] : '-';
////        }
//
//        return $stat;
//    }
//
//    public function get_stat_titless()
//    {
//        $titles = ['Season', 'Level'];
//
//        $stat_arr = $this->{$this->position . "_stat_arr"};
//        foreach ($stat_arr as $label) {
//            $titles[] = $label;
//        }
//
//        return $titles;
//    }
//
//}


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class panini_mlb_players_league_stat extends Model
{
    use HasFactory;

    protected $table = 'panini_mlb_players_league_stat';

    protected $fillable = [
        'player_id', 'season', 'level', 'stat', 'position'
    ];

//    protected $casts = [
//        'stat' => 'array',
//    ];

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

    public function show_stats()
    {
        $stat = [];
        $stat[] = $this->season;
        $stat[] = $this->level;

        $player_stat = json_decode($this->stat);
        foreach ($this->{$this->position . "_stat_arr"} as $key => $stat_arr) {
            $stat[] = property_exists($player_stat, $key) ? $player_stat->{$key} : "-";
        }
        return $stat;
    }

    public function get_stat_titless()
    {
        $stat_arr = $this->{$this->position . "_stat_arr"};
        foreach ($stat_arr as $label) {
            $titles[] = $label;
        }
        return $titles;
    }

    public function show_positions()
    {
        if ($this->stats->count()) {
            return $this->stats->first()->position;
        } else {
            return false;
        }
    }
}
