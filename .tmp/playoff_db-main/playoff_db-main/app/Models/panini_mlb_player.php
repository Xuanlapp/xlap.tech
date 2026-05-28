<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\panini_mlb_players_stat;
use App\Models\panini_mlb_players_league_stat;
use App\Models\mlb_team;

class panini_mlb_player extends Model
{
    use HasFactory;

    protected $fillable = [
        'player',
        'mlb_player_id',
        'team_name',
        'first_name',
        'last_name',
        'team_name',
        'active',
        'jersey_number',
        'position',
        'marked',
        'panini_position',
        'panini_team',
        'panini_id',
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

    /**
     * 建立 Player 模型到 PlayerStat 模型的一對多關係
     */
    public function stats()
    {
        return $this->hasMany(panini_mlb_players_stat::class, 'player_id');
    }

    public function leaguestats()
    {
        return $this->hasMany(panini_mlb_players_league_stat::class, 'player_id');
    }

    /**
     * 在 Player 模型中保存 PlayerStat
     */
    public function savePlayerStat(array $statData)
    {
        // 建立新的 PlayerStat 實例
        $playerStat = new panini_mlb_players_stat($statData);

        // 設定與球員的關聯
        $this->stats()->save($playerStat);
    }

    public function savePlayerleagueStat(array $statData)
    {
        $playerStat = new panini_mlb_players_league_stat($statData);


        // 設定與球員的關聯
        $this->leaguestats()->save($playerStat);
    }

    public function show_career()
    {
        $career = json_decode($this->career_stat);
        if ($this->show_position()) {
            foreach ($this->{$this->show_position() . "_stat_arr"} as $key => $stat_arr) {
                $output_career_stat[] = $career->{$key};
            }
        } else {
            $output_career_stat = false;
        }
        return $output_career_stat;
    }

    public function show_stat_with_quantity($takeCount)
    {
        return $this->stats()->orderBy('id', 'desc')->take($takeCount)->get();
    }


    public function show_position()
    {
        if ($this->stats->count()) {
            return $this->stats->first()->position;
        } else {
            return false;
        }
    }

    public function show_teams_played()
    {
//        dd('lapdz');
        if ($this->teams_played) {
            return implode(", ", json_decode($this->teams_played));
        } else {
            return "";
        }
    }

    public function player_img_url()
    {
        return "https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_639,q_auto:best/v1/people/" . $this->mlb_player_id . "/headshot/67/current";
    }

    public function team_icon()
    {
        $team = mlb_team::where('team_name', $this->last_played_team)->first();
        if ($team) {
            if ($team->team_num !== null) {
                return "https://www.mlbstatic.com/team-logos/team-cap-on-light/" . $team->team_num . ".svg";
            }
        }
    }

    public function show_stat_title()
    {
        if ($this->show_position()) {
            return $this->{$this->show_position() . "_stat_arr"};
        }
    }

}
