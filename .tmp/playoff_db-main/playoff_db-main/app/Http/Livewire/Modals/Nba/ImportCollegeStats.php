<?php

namespace App\Http\Livewire\Modals\Nba;

use App\Models\Panini_nba_player;
use App\Models\panini_nba_players_college_stats;
use LivewireUI\Modal\ModalComponent;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportCollegeStats extends ModalComponent
{
    use WithFileUploads;

    public $file_nba_college;

    public function render()
    {
        return view('livewire.modals.nba.import-college-stats');
    }

    public function import()
    {
        $this->validate([
            'file_nba_college' => 'required|file',
        ]);

        if (!$this->file_nba_college) {
            session()->flash('error', 'Không có tệp nào được tải lên.');
            return;
        }
        try {
            $data = Excel::toArray([], $this->file_nba_college->getRealPath())[0];

            $playerName = null;
            foreach ($data as $row) {
                if (!empty($row[0]) && $row[1] === null && $row[2] === null) {
                    $playerName = $row[0];
                    continue;
                }

                if ($playerName && $row[0] === "Season") {
                    continue;
                }

                if ($playerName) {

                    $player = Panini_nba_player::where('player', $playerName)->first();

                    if ($player) {

                        $playerId = $player->id;

                        if ($player->marked == 0 || $player->marked == 1) {
                            $player->marked = 2;
                        }

                        if (strpos($row[0], 'Totals') !== false) {
                            // 建立職業生涯統計數據陣列
                            $careerStats = [
                                'G' => $row[2] ?? null,
                                'FG%' => $row[3] ?? null,
                                'FT%' => $row[4] ?? null,
                                '3PM' => $row[5] ?? null,
                                'RPG' => $row[6] ?? null,
                                'APG' => $row[7] ?? null,
                                'STL' => $row[8] ?? null,
                                'BLK' => $row[9] ?? null,
                                'PTS' => $row[10] ?? null,
                                'PPG' => $row[11] ?? null,
                            ];

                            // 保存舊的職業生涯統計數據
                            // 這邊先不保存，因為我們要保留最新的職業生涯統計數據
                            // if ($player->college_career_stats) {
                            //     $player->last_year_college_career_stats = $player->college_career_stats;
                            // }

                            // 更新球員的職業生涯統計數據
                            $player->college_career_stats = $careerStats;
                            $player->career_stats_last_updated_at = now();
                            $player->save();
                        } else {
                            // 處理單一賽季數據
                            $seasonParts = explode('-', $row[0]);
                            if (count($seasonParts) == 2) {
                                // 轉換年份格式
                                $convertedYear = '20' . $seasonParts[0] . '-' . $seasonParts[1];

                                // 檢查該賽季數據是否已存在
                                $existingStat = panini_nba_players_college_stats::where('player_id', $playerId)
                                    ->where('year', $convertedYear)
                                    ->first();
                                if (!$existingStat) {
                                    // 建立新的賽季數據
                                    $seasonData = [
                                        'player_id' => $playerId,
                                        'year' => $convertedYear,
                                        'team' => $row[1],
                                        'g' => $row[2],
                                        'fg%' => $row[3],
                                        'ft%' => $row[4],
                                        '3pm' => $row[5],
                                        'rpg' => $row[6],
                                        'apg' => $row[7],
                                        'stl' => $row[8],
                                        'blk' => $row[9],
                                        'pts' => $row[10],
                                        'ppg' => $row[11],
                                    ];
                                    panini_nba_players_college_stats::create($seasonData);
                                }
                            }
                        }
                    } else {
                        // 如果找不到球員，建立新球員資料
                        $newPlayer = [
                            'player' => $playerName,
                            'marked' => 2,
                        ];
                        Panini_nba_player::create($newPlayer);
                    }
                }
            }
            session()->flash('success', 'The data has been imported successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error when entering data: ' . $e->getMessage());
        }

        $this->file_nba_college = null;
    }


    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
