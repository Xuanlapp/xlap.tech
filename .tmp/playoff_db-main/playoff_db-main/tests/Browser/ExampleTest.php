<?php

namespace Tests\Browser;

use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\nba_players_official;
use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use App\Models\PaniniNbaPlayerStats;
use App\Models\Panini_mlb_player;
use App\Models\panini_retured_player;
use App\Http\Livewire\Traits\ScrapeData;
use App\Http\Livewire\Traits\MlbScrapingData;
use App\Models\panini_retired_player;
use App\Models\panini_player;

class ExampleTest extends DuskTestCase
{
    use ScrapeData, MlbScrapingData;
    public function testBasicExample(): void
    {

        // $this->confirmNbaPlayer();

        $players = Panini_nba_player::where('marked', 0)->get();
        foreach ($players as $player) {
            $player->marked = 1;
            $player->save();
        }
    }

    public function tempFunc($players)
    {
        foreach ($players as $player) {
            $player->marked = 1;
            $player->save();
        }
    }

    public function compareTeam($players)
    {
        foreach ($players as $key => $player) {
            if ($this->compare_all_team_match($player->teams_played, $player->panini_team)) {
                $player->team_match = 1;
                $player->save();
            } else {
                $player->team_match = 0;
                $player->save();
            }
        }
    }

    public function tempteap($players)
    {
        foreach ($players as $key => $player) {
            $panini_player = panini_mlb_player::find($player->match_mlb_player);
            $player->last_played_team = $panini_player->last_played_team;
            $player->save();
        }
    }

    private function handleTeamName()
    {
        $teams = Panini_nba_player::pluck('team_name')->unique()->toArray();
        foreach ($teams as $team) {
            $match_team = Nba_team::where('team_name', $team)->first();
            if (!$match_team) {
                Nba_team::create(['team_name' => $team]);
            }
        }
    }

    public function searchBbRefFromBing()
    {
        $this->browse(function (Browser $browser) {
            $players = Panini_nba_player::where('marked', 5)->get();

            // Visit Bing Search
            foreach ($players as $key => $player) {
                $links = $browser->visit("https://www.bing.com")
                    ->type('q', $player->player . ' ' . ' basketball reference')
                    ->click('#search_icon') // 使用 Xpath 定位搜索按钮并点击
                    ->waitFor('.b_algo')
                    ->elements('.b_algo a');
                // $browser->pause(5000);
                // 等待搜索结果加载完成
                $found = 0;
                foreach ($links as $link) {
                    //print_r($link->getAttribute('href') . "\n");
                    if (strpos($link->getAttribute('href'), 'www.basketball-reference.com/players')) {
                        $bb_link = $link->getAttribute('href');
                        $player->bb_ref = $bb_link;
                        $player->marked = 1;
                        $player->save();
                        break;
                        $found = 1;
                    }
                }
                // $browser->pause(1000);
            }
        });
    }

    public function filterType()
    {
        $this->browse(function (Browser $browser) {
            $players = Panini_nba_player::where('marked', 2)->where('espn_player_id', NULL)->get();

            foreach ($players as $key => $player) {
                $links = $browser->visit('https://www.bing.com')
                    ->type('q', $player->player . ' espn player id')
                    ->click('#search_icon') // 使用 Xpath 定位搜索按钮并点击
                    ->waitFor('.b_algo')
                    ->elements('.b_algo a');

                // 等待搜索结果加载完成
                foreach ($links as $link) {
                    //print_r($link->getAttribute('href') . "\n");
                    if (strpos($link->getAttribute('href'), 'www.espn.com/nba/player')) {
                        preg_match('/(\d+)/', $link->getAttribute('href'), $matches);
                        if (isset($matches[1])) {
                            $player->espn_player_id = $matches[1];
                            $player->marked = 2;
                            $player->type = 1;
                            $player->save();
                            echo $player->id . ". " . $player->player . " espn player_id: " . $matches[1] . "\n";
                            break;
                        }
                    }
                    if (strpos($link->getAttribute('href'), 'www.espn.com/mens-college-basketball/player')) {
                        preg_match('/(\d+)/', $link->getAttribute('href'), $matches);
                        if (isset($matches[1])) {
                            $player->espn_player_id = $matches[1];
                            $player->marked = 0;
                            $player->type = 2;
                            $player->save();
                            echo $player->id . ". " . $player->player . " espn player_id: " . $matches[1] . "\n";
                            break;
                        }
                    }
                }
            }
        });
    }

    public function confirmNbaPlayer()
    {
        $this->browse(function (Browser $browser) {
            // $player_arr = [1, 2, 3];
            $players = Panini_nba_player::where('marked', 2)->get();

            foreach ($players as $key => $player) {
                // print_r($player->id . "\n");
                $browser->visit("https://www.nba.com/stats/player/{$player->nba_player_id}/career?PerMode=Totals");
                try {
                    $browser->waitFor('.PlayerSummary_mainInnerBio__JQkoj');
                    // 继续执行您的代码逻辑
                    $ps = $browser->elements('.PlayerSummary_mainInnerBio__JQkoj p');
                    $nba_fullname = $ps[1]->getText() . " " . $ps[2]->getText();
                    // Check is the name match

                    $profile_arr = explode('|', $ps[0]->getText());
                    $data['first_name'] = trim($ps[1]->getText());
                    $data['last_name'] = trim($ps[2]->getText());
                    $data['team_name'] = trim($profile_arr[0]);
                    $data['jersey_number'] = str_replace('#', '', trim($profile_arr[1]));
                    $data['position'] = trim($profile_arr[2]);
                    // $data['marked'] = 1;
                    $player->update($data);

                    // foreach ($ps as $p) {
                    //     print_r($p->getText() . "\n");
                    // }
                } catch (\Exception $e) {
                }
            }
        });
    }

    public function confirmEspnPlayer()
    {
        $this->browse(function (Browser $browser) {
            // $player_arr = [1, 2, 3];
            $players = Panini_nba_player::where('marked', 2)->get();

            foreach ($players as $key => $player) {
                // print_r($player->id . "\n");
                $browser->visit("https://www.espn.com/nba/player/stats/_/id/{$player->espn_player_id}");
                try {
                    $browser->waitFor('.PlayerHeader__Name');
                    // 继续执行您的代码逻辑
                    $spans = $browser->elements('.PlayerHeader__Name span');
                    $nba_fullname = $spans[0]->getText() . " " . $spans[1]->getText();
                    $li = $browser->elements('.PlayerHeader__Team_Info li');
                    // Check is the name match
                    if ($nba_fullname == strtoupper($player->player)) {
                        $data['first_name'] = trim($spans[0]->getText());
                        $data['last_name'] = trim($spans[1]->getText());
                        $data['position'] = trim($li[0]->getText());
                        $data['marked'] = 5;
                        $player->update($data);
                    }
                } catch (\Exception $e) {
                }
            }
        });
    }

    public function searchNbaPlayerFromBing()
    {
        $this->browse(function (Browser $browser) {
            $players = Panini_nba_player::where('marked', 2)->get();

            // Visit Bing Search
            foreach ($players as $key => $player) {
                $links = $browser->visit("https://www.bing.com")
                    ->type('q', $player->player . ' nba player id')
                    ->click('#search_icon') // 使用 Xpath 定位搜索按钮并点击
                    ->waitFor('.b_algo')
                    ->elements('.b_algo a');
                // $browser->pause(5000);
                // 等待搜索结果加载完成
                $found = 0;
                foreach ($links as $link) {
                    //print_r($link->getAttribute('href') . "\n");
                    if (strpos($link->getAttribute('href'), 'www.nba.com/player')) {
                        preg_match('/(\d+)/', $link->getAttribute('href'), $matches);
                        if (isset($matches[1])) {
                            $nba_id = $matches[1];
                            $player->nba_player_id = $nba_id;
                            $player->marked = 3;
                            $player->save();
                            echo $player->id . ". " . $player->player . " nba player_id" . $nba_id . "\n";
                            break;
                            $found = 1;
                        }
                    }
                    if (strpos($link->getAttribute('href'), 'www.nba.com/stats')) {
                        preg_match('/(\d+)/', $link->getAttribute('href'), $matches);
                        if (isset($matches[1])) {
                            $nba_id = $matches[1];
                            $player->nba_player_id = $nba_id;
                            $player->marked = 3;
                            $player->save();
                            echo $player->id . ". " . $player->player . " nba player_id" . $nba_id . "\n";
                            $found = 1;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $player->marked = 2;
                    $player->save();
                }
                // $browser->pause(1000);
            }
        });
    }


    public function gettingTeamId()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.nba.com/teams');
            $browser->waitFor('div.TeamFigure_tfContent__Vxiyh');
            $tableData = $browser->driver->executeScript('
                let tableData = [];
                document.querySelectorAll("div.TeamFigure_tfContent__Vxiyh").forEach(element => {
                    let teamName = element.querySelector("a:first-child").innerText.trim();
                    let teamLink = element.querySelector("div a:nth-child(2)").href;
                    if (teamLink){
                        tableData.push({
                            team : teamName,
                            link : teamLink
                        });
                    }
                });
                return JSON.stringify(tableData);
            ');

            $tableData = json_decode($tableData, true);
            foreach ($tableData as $element) {
                $team = Nba_team::where('team_name', $element['team'])->first();
                if ($team) {
                    $link_explode_arr = explode('/', $element['link']);
                    $team->team_id = $link_explode_arr[count($link_explode_arr) - 1];
                    $team->save();
                }
            }

            print_r($tableData);
        });
    }
    /**
     * testBasicExample
     *
     * @return void
     */
    public function basicExample(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.nba.com/stats/players/bio?Season=2022-23&SeasonType=Regular+Season&PerMode=Totals');
            // click on the pagination selection
            $browser->driver->findElement(WebDriverBy::xpath('//*[@id="__next"]/div[2]/div[2]/div[3]/section[2]/div/div[2]/div[2]/div[1]/div[3]/div/label/div/select'))->click();
            $browser->pause(3000);
            // click on the option 1, which is the all option
            $browser->waitUsing(5, 100, function () use ($browser) {
                return $browser->driver->findElement(WebDriverBy::xpath('//*[@id="__next"]/div[2]/div[2]/div[3]/section[2]/div/div[2]/div[2]/div[1]/div[3]/div/label/div/select/option[1]'))->click();
            });
            $browser->pause(300);

            $tableData = $browser->driver->executeScript('
                let tableData = [];
                document.querySelectorAll("table tr").forEach(tr => {
                    let playerLink = tr.querySelector("td:first-child a");
                    if (playerLink){
                        tableData.push({
                            player: playerLink.innerText.trim(),
                            nba_link: playerLink.href,
                            team: tr.querySelector("td:nth-child(2)").innerText.trim()
                        });
                    }
                });
                return JSON.stringify(tableData);
            ');

            $tableData = json_decode($tableData, true);

            $count = 0;

            foreach ($tableData as $player) {
                $player['player_id'] = basename($player['nba_link']);
                nba_players_official::create($player);
                $count++;
            }

            print_r("completed! " . $count . " players have been scraped.");
        });
    }
}
