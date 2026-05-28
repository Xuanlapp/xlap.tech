<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\panini_nba_player;
use App\Models\PaniniNbaPlayerStats;
use App\Http\Livewire\Modals\Nba\UpdatePlayerStat;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdatePlayerStatTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_different_career_stats_for_current_and_last_year()
    {
        // 創建測試球員
        $player = panini_nba_player::factory()->create();

        // 創建多年的統計數據
        $stats = [
            [
                'year' => '2024',
                'g' => 82,
                'fgm' => 500,
                'fga' => 1000,
                'ftm' => 200,
                'fta' => 250,
                '3pm' => 100,
                'rpg' => 10,
                'apg' => 5,
                'stl' => 100,
                'blk' => 50,
                'pts' => 1200,
                'ppg' => 14.6
            ],
            [
                'year' => '2023',
                'g' => 82,
                'fgm' => 450,
                'fga' => 900,
                'ftm' => 180,
                'fta' => 220,
                '3pm' => 90,
                'rpg' => 9,
                'apg' => 4,
                'stl' => 90,
                'blk' => 45,
                'pts' => 1100,
                'ppg' => 13.4
            ],
            [
                'year' => '2022',
                'g' => 82,
                'fgm' => 400,
                'fga' => 800,
                'ftm' => 160,
                'fta' => 200,
                '3pm' => 80,
                'rpg' => 8,
                'apg' => 3,
                'stl' => 80,
                'blk' => 40,
                'pts' => 1000,
                'ppg' => 12.2
            ]
        ];

        foreach ($stats as $stat) {
            PaniniNbaPlayerStats::create(array_merge($stat, [
                'player_id' => $player->id
            ]));
        }

        // 執行更新
        $updateStat = new UpdatePlayerStat(['player_ids' => [$player->id]]);
        $result = $updateStat->calculateCareerStats($player);

        // 驗證結果不為空
        $this->assertNotNull($result);
        $this->assertArrayHasKey('career_stats', $result);
        $this->assertArrayHasKey('last_year_career_stats', $result);

        // 驗證 career_stats 和 last_year_career_stats 是否不同
        $this->assertNotEquals(
            $result['career_stats'],
            $result['last_year_career_stats'],
            'Career stats should be different from last year career stats'
        );

        // 驗證具體數值
        $careerStats = $result['career_stats'];
        $lastYearStats = $result['last_year_career_stats'];

        // 驗證場次
        $this->assertEquals(246, $careerStats['g'], 'Total games should be 246');
        $this->assertEquals(164, $lastYearStats['g'], 'Last year total games should be 164');

        // 驗證投籃命中率
        $this->assertEquals(0.5, $careerStats['fg%'], 'Career FG% should be 50%');
        $this->assertEquals(0.494, $lastYearStats['fg%'], 'Last year FG% should be 49.4%');

        // 驗證得分
        $this->assertEquals(3300, $careerStats['pts'], 'Career points should be 3300');
        $this->assertEquals(2100, $lastYearStats['pts'], 'Last year points should be 2100');
    }

    /** @test */
    public function it_handles_single_year_stats_correctly()
    {
        // 創建測試球員
        $player = panini_nba_player::factory()->create();

        // 只創建一年的統計數據
        PaniniNbaPlayerStats::create([
            'player_id' => $player->id,
            'year' => '2024',
            'g' => 82,
            'fgm' => 500,
            'fga' => 1000,
            'ftm' => 200,
            'fta' => 250,
            '3pm' => 100,
            'rpg' => 10,
            'apg' => 5,
            'stl' => 100,
            'blk' => 50,
            'pts' => 1200,
            'ppg' => 14.6
        ]);

        // 執行更新
        $updateStat = new UpdatePlayerStat(['player_ids' => [$player->id]]);
        $result = $updateStat->calculateCareerStats($player);

        // 驗證結果
        $this->assertNotNull($result);
        $this->assertNotNull($result['career_stats'], 'Career stats should not be null');
        $this->assertNull($result['last_year_career_stats'], 'Last year career stats should be null for single year');

        // 驗證具體數值
        $careerStats = $result['career_stats'];
        $this->assertEquals(82, $careerStats['g'], 'Games should be 82');
        $this->assertEquals(0.5, $careerStats['fg%'], 'FG% should be 50%');
        $this->assertEquals(1200, $careerStats['pts'], 'Points should be 1200');
    }

    /** @test */
    public function it_handles_ac_green_like_stats_correctly()
    {
        // 創建測試球員
        $player = panini_nba_player::factory()->create();

        // 創建類似 A.C. Green 的統計數據
        $stats = [
            // 2000 年數據
            [
                'year' => '2000',
                'g' => 82,
                'fgm' => 200,
                'fga' => 400,
                'ftm' => 100,
                'fta' => 150,
                '3pm' => 10,
                'rpg' => 7.5,
                'apg' => 1.0,
                'stl' => 50,
                'blk' => 30,
                'pts' => 500,
                'ppg' => 6.1
            ],
            // 1999 年數據
            [
                'year' => '1999',
                'g' => 82,
                'fgm' => 250,
                'fga' => 500,
                'ftm' => 120,
                'fta' => 160,
                '3pm' => 15,
                'rpg' => 8.0,
                'apg' => 1.2,
                'stl' => 60,
                'blk' => 35,
                'pts' => 600,
                'ppg' => 7.3
            ],
            // 1998 年數據
            [
                'year' => '1998',
                'g' => 82,
                'fgm' => 300,
                'fga' => 600,
                'ftm' => 140,
                'fta' => 180,
                '3pm' => 20,
                'rpg' => 8.5,
                'apg' => 1.4,
                'stl' => 70,
                'blk' => 40,
                'pts' => 700,
                'ppg' => 8.5
            ]
        ];

        foreach ($stats as $stat) {
            PaniniNbaPlayerStats::create(array_merge($stat, [
                'player_id' => $player->id
            ]));
        }

        // 執行更新
        $updateStat = new UpdatePlayerStat(['player_ids' => [$player->id]]);
        $result = $updateStat->calculateCareerStats($player);

        // 驗證結果
        $this->assertNotNull($result);

        $careerStats = $result['career_stats'];
        $lastYearStats = $result['last_year_career_stats'];

        // 驗證總場次
        $this->assertEquals(246, $careerStats['g'], 'Career total games should be 246');
        $this->assertEquals(164, $lastYearStats['g'], 'Last year total games should be 164');

        // 驗證得分
        $this->assertEquals(1800, $careerStats['pts'], 'Career total points should be 1800');
        $this->assertEquals(1300, $lastYearStats['pts'], 'Last year total points should be 1300');

        // 驗證平均數據
        $this->assertEquals(8.0, $careerStats['rpg'], 'Career RPG should be 8.0');
        $this->assertEquals(8.25, $lastYearStats['rpg'], 'Last year RPG should be 8.25');
    }
}
