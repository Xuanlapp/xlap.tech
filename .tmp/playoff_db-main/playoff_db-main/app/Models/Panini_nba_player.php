<?php

namespace App\Models;

use App\Models\Nba_team;
use App\Models\nba_player_id_raw;
use App\Models\panini_nba_players_college_stats;
use App\Models\PaniniNbaPlayerStats;
use App\Models\Traits\FormatStatsTrait;
use Illuminate\Database\Eloquent\Model;

class Panini_nba_player extends Model
{
    use FormatStatsTrait;

    protected $fillable = [
        'player',
        'nba_player_id',
        'team_name',
        'player_id',
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
        'espn_player_id',
        'type',
        'bb_ref',
        'latest_season',
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
        'career_stats',
        'last_year_career_stats',
        'college_career_stats',
        'last_year_college_career_stats',
        'career_stats_last_updated_at'
    ];

    protected $casts = [
        'career_stats' => 'array',
        'last_year_career_stats' => 'array',
        'college_career_stats' => 'array',
        'career_stats_last_updated_at' => 'datetime'
    ];

    /**
     * 保存 PlayerStat 到 Player 模型中
     */
    public function savePlayerStat(array $statData)
    {
        // 檢查是否已存在相同年份的數據
        $existingStat = $this->stats()->where('year', $statData['year'])->first();

        if ($existingStat) {
            // 如果存在，更新現有數據
            $existingStat->update($statData);
            $existingStat->last_updated_at = now();
            $existingStat->save();
        } else {
            // 如果不存在，創建新數據
            $playerStat = new PaniniNbaPlayerStats($statData);
            $this->stats()->save($playerStat);
        }
    }

    public function savePlayerCollegeStat(array $statData)
    {
        $playerStat = new panini_nba_players_college_stats($statData);
        $this->college_stats()->save($playerStat);
    }

    /**
     * 一对多关系：Player 到 PlayerStat 模型
     */
    public function stats()
    {
        return $this->hasMany(PaniniNbaPlayerStats::class, 'player_id');
    }

    public function college_stats()
    {
        return $this->hasMany(panini_nba_players_college_stats::class, 'player_id');
    }

    public function show_stat_with_quantity($takeCount)
    {
        return $this->stats()->orderBy('id', 'desc')->take($takeCount)->get();
    }

    public function show_college_stat_with_quantity($takeCount)
    {
        return $this->college_stats()->orderBy('id', 'desc')->take($takeCount)->get();
    }

    public function show_stat_title()
    {
        return ['Season', 'Team', 'G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
    }

    public function show_career_header()
    {
        return ['G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
    }


    public function show_career()
    {
        $careerStat = [];
        $statTitles = $this->show_career_header();

        if (!empty($this->career_stats)) {
            foreach ($statTitles as $title) {
                $value = $this->career_stats[$title] ?? '-';

                // 检查值是否为数字且不是 "-"
                if (is_numeric($value) && $value !== '-') {
                    // 根据统计类型进行格式化
                    if (in_array($title, ['FG%', 'FT%'])) {
                        $value = $this->formatPercentage($value);
                    } elseif (in_array($title, ['RPG', 'APG', 'PPG'])) {
                        $value = $this->formatAverage($value);
                    }
                }

                $careerStat[] = $value;
            }
        } else {
            // 如果没有career_stats数据，填充"-"
            $careerStat = array_fill(0, count($statTitles), '-');
        }

        return $careerStat;
    }

    /**
     * 显示去年生涯统计数据
     */
    public function show_last_year_career()
    {
        $lastYearCareerStat = [];
        $statTitles = $this->show_career_header();

        if (!empty($this->last_year_career_stats)) {
            foreach ($statTitles as $title) {
                $value = $this->last_year_career_stats[$title] ?? '-';

                // 检查值是否为数字且不是 "-"
                if (is_numeric($value) && $value !== '-') {
                    // 根据统计类型进行格式化
                    if (in_array($title, ['FG%', 'FT%'])) {
                        $value = $this->formatPercentage($value);
                    } elseif (in_array($title, ['RPG', 'APG', 'PPG'])) {
                        $value = $this->formatAverage($value);
                    }
                }

                $lastYearCareerStat[] = $value;
            }
        } else {
            // 如果没有last_year_career_stats数据，填充"-"
            $lastYearCareerStat = array_fill(0, count($statTitles), '-');
        }

        return $lastYearCareerStat;
    }

    public function show_college_career()
    {
        $careerCollegeStat = [];
        $statTitles = $this->show_career_header();

        if (!empty($this->college_career_stats)) {
            foreach ($statTitles as $title) {
                $value = $this->college_career_stats[$title] ?? '-';

                // 检查值是否为数字且不是 "-"
                if (is_numeric($value) && $value !== '-') {
                    // 根据统计类型进行格式化
                    if (in_array($title, ['FG%', 'FT%'])) {
                        $value = $this->formatPercentage($value);
                    } elseif (in_array($title, ['RPG', 'APG', 'PPG'])) {
                        $value = $this->formatAverage($value);
                    }
                }

                $careerCollegeStat[] = $value;
            }
        } else {
            // 如果没有college_career_stats数据，填充"-"
            $careerCollegeStat = array_fill(0, count($statTitles), '-');
        }

        return $careerCollegeStat;
    }

    /**
     * 显示去年大学生涯统计数据
     */
    public function show_last_year_college_career()
    {
        $lastYearCollegeCareerStat = [];
        $statTitles = $this->show_career_header();

        if (!empty($this->last_year_college_career_stats)) {
            foreach ($statTitles as $title) {
                $value = $this->last_year_college_career_stats[$title] ?? '-';

                // 检查值是否为数字且不是 "-"
                if (is_numeric($value) && $value !== '-') {
                    // 根据统计类型进行格式化
                    if (in_array($title, ['FG%', 'FT%'])) {
                        $value = $this->formatPercentage($value);
                    } elseif (in_array($title, ['RPG', 'APG', 'PPG'])) {
                        $value = $this->formatAverage($value);
                    }
                }

                $lastYearCollegeCareerStat[] = $value;
            }
        } else {
            // 如果没有last_year_college_career_stats数据，填充"-"
            $lastYearCollegeCareerStat = array_fill(0, count($statTitles), '-');
        }

        return $lastYearCollegeCareerStat;
    }

    public function team_icon($style)
    {
        $team = Nba_team::where('team_name', $this->team_name)->first();
        if ($team && $team->team_abb !== null) {
            $logoType = $style == "L" ? "L" : "D";
            return "https://cdn.nba.com/logos/nba/{$team->team_id}/global/{$logoType}/logo.svg";
        }
        return 0;
    }

    public function team_kind()
    {
        $team = Nba_team::where('team_name', $this->team_name)->first();
        return $team ? $team->kind : null;
    }

    public function team_id()
    {
        $team = Nba_team::where('team_name', $this->team_name)->first();
        return $team && $team->team_abb !== null ? $team->team_id : null;
    }

    public function team_color()
    {
        $team = Nba_team::where('team_name', $this->team_name)->first();
        return $team && $team->team_abb !== null ? $team->team_color : "#000000";
    }

    public function player_image_url()
    {
        return "https://cdn.nba.com/headshots/nba/latest/1040x760/{$this->nba_player_id}.png";
    }

    /**
     * 計算並更新生涯統計數據
     */
    public function calculateAndUpdateCareerStats()
    {
        $stats = $this->stats()->orderBy('year')->get();

        if ($stats->isEmpty()) {
            $this->career_stats = null;
            $this->latest_year = null;
            $this->save();
            return;
        }

        // 保存當前的生涯統計作為去年的數據
        if ($this->career_stats) {
            $this->last_year_career_stats = $this->career_stats;
        }

        // 更新最新賽季年份
        $this->latest_year = $stats->max('year');

        // 計算新的生涯統計
        $careerStats = [
            'G' => $stats->sum('g'),
            '3PM' => $stats->sum('3pm'),
            'STL' => $stats->sum('stl'),
            'BLK' => $stats->sum('blk'),
            'PTS' => $stats->sum('pts'),
            'RPG' => round($stats->avg('rpg'), 1),
            'APG' => round($stats->avg('apg'), 1),
            'PPG' => round($stats->avg('ppg'), 1)
        ];

        // 檢查是否有完整的投籃數據
        $hasCompleteFgData = $stats->every(function ($stat) {
            return !is_null($stat->fgm) && !is_null($stat->fga) && $stat->fga > 0;
        });

        $hasCompleteFtData = $stats->every(function ($stat) {
            return !is_null($stat->ftm) && !is_null($stat->fta) && $stat->fta > 0;
        });

        if ($hasCompleteFgData) {
            $totalFgm = $stats->sum('fgm');
            $totalFga = $stats->sum('fga');
            $careerStats['FG%'] = $totalFga > 0 ? round($totalFgm / $totalFga * 100, 1) : null;
        }

        if ($hasCompleteFtData) {
            $totalFtm = $stats->sum('ftm');
            $totalFta = $stats->sum('fta');
            $careerStats['FT%'] = $totalFta > 0 ? round($totalFtm / $totalFta * 100, 1) : null;
        }

        $this->career_stats = $careerStats;
        $this->career_stats_last_updated_at = now();
        $this->save();
    }

    /**
     * 獲取生涯統計數據
     */
    public function getCareerStats()
    {
        // 如果沒有生涯統計或最後更新時間超過24小時，重新計算
        if (
            !$this->career_stats ||
            !$this->career_stats_last_updated_at ||
            $this->career_stats_last_updated_at->diffInHours(now()) > 24
        ) {
            $this->calculateAndUpdateCareerStats();
        }

        return [
            'current' => $this->career_stats,
            'last_year' => $this->last_year_career_stats
        ];
    }

    /**
     * 獲取去年的生涯統計數據
     */
    public function getLastYearCareerStats()
    {
        return $this->last_year_career_stats;
    }

    /**
     * 計算並更新大學生涯統計數據
     */
    public function calculateAndUpdateCollegeCareerStats()
    {
        $stats = $this->college_stats()->get();

        if ($stats->isEmpty()) {
            $this->college_career_stats = null;
            $this->save();
            return;
        }

        // 保存當前的大學生涯統計作為去年的數據
        if ($this->college_career_stats) {
            $this->last_year_college_career_stats = $this->college_career_stats;
        }

        // 計算新的大學生涯統計
        $careerStats = [
            'G' => $stats->sum('g'),
            '3PM' => $stats->sum('3pm'),
            'STL' => $stats->sum('stl'),
            'BLK' => $stats->sum('blk'),
            'PTS' => $stats->sum('pts'),
            'RPG' => round($stats->avg('rpg'), 1),
            'APG' => round($stats->avg('apg'), 1),
            'PPG' => round($stats->avg('ppg'), 1)
        ];

        // 檢查是否有完整的投籃數據
        $hasCompleteFgData = $stats->every(function ($stat) {
            return !is_null($stat->fgm) && !is_null($stat->fga) && $stat->fga > 0;
        });

        $hasCompleteFtData = $stats->every(function ($stat) {
            return !is_null($stat->ftm) && !is_null($stat->fta) && $stat->fta > 0;
        });

        if ($hasCompleteFgData) {
            $totalFgm = $stats->sum('fgm');
            $totalFga = $stats->sum('fga');
            $careerStats['FG%'] = $totalFga > 0 ? round($totalFgm / $totalFga * 100, 1) : null;
        }

        if ($hasCompleteFtData) {
            $totalFtm = $stats->sum('ftm');
            $totalFta = $stats->sum('fta');
            $careerStats['FT%'] = $totalFta > 0 ? round($totalFtm / $totalFta * 100, 1) : null;
        }

        $this->college_career_stats = $careerStats;
        $this->career_stats_last_updated_at = now();
        $this->save();
    }

    /**
     * 獲取大學生涯統計數據
     */
    public function getCollegeCareerStats()
    {
        // 如果沒有生涯統計或最後更新時間超過24小時，重新計算
        if (
            !$this->college_career_stats ||
            !$this->career_stats_last_updated_at ||
            $this->career_stats_last_updated_at->diffInHours(now()) > 24
        ) {
            $this->calculateAndUpdateCollegeCareerStats();
        }

        return [
            'current' => $this->college_career_stats,
            'last_year' => $this->last_year_college_career_stats
        ];
    }

    /**
     * 獲取大學去年的生涯統計數據
     */
    public function getLastYearCollegeCareerStats()
    {
        return $this->last_year_college_career_stats;
    }

    /**
     * 更新所有生涯統計數據
     */
    public function updateAllCareerStats()
    {
        $this->calculateAndUpdateCareerStats();
        $this->calculateAndUpdateCollegeCareerStats();
    }
}
