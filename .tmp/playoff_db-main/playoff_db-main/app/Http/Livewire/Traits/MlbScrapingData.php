<?php

namespace App\Http\Livewire\Traits;

use Laravel\Dusk\Browser;
use App\Http\Livewire\Traits\MlbDownloadData;
use App\Models\panini_mlb_player;
use App\Models\panini_retired_player;

trait MlbScrapingData
{
    use MlbDownloadData;

    private function addPlayers()
    {
        $panini_players = panini_retired_player::where('marked', null)->get();
        foreach ($panini_players as $panini_player) {
            $player['player'] = $panini_player->name;
            $player['marked'] = 5;
            panini_mlb_player::create($player);
        }
    }
    private function manualMatch()
    {
        $panini_players = panini_retired_player::all();
        foreach ($panini_players as $panini_player) {
            $mlb_player = panini_mlb_player::where('id', $panini_player->match_mlb_player)->first();
            if ($mlb_player) {
                $panini_player->panini_id = $mlb_player->panini_id;
                // $panini_player->match_mlb_player = $mlb_player->id;
                $panini_player->save();
            }
        }
    }

    private function matchLastName($players)
    {
        foreach ($players as $key => $player) {
            $nameArr = explode(' ', $player->player);
            $lastname = $nameArr[count($nameArr) - 1];
            if ($lastname == $player->last_name) {
                $player->marked = 1;
                $player->save();
            }
        }
    }

    private function matchingPlayerFullname($players)
    {
        foreach ($players as $key => $player) {
            $player_info = $this->getPlayerFullname($player->mlb_player_id);
            if ($player_info !== null) {
                $player->full_name = $player_info['full_name'];
                $player->first_name = $player_info['first_name'];
                $player->last_name = $player_info['last_name'];
                $player->middle_name = $player_info['middle_name'];

                if ($player_info['full_name'] == trim($player->player)) {
                    $player->marked = 4;
                } else {
                    $player->marked = 1;
                }
                $player->save();
            }
        }
    }
    // private function searchMlbPlayerId($players)
    // {
    //     $this->browse(
    //         function (Browser $browser) use ($players) {
    //             foreach ($players as $key => $player) {
    //                 try {
    //                     $browser->visit("https://www.bing.com")
    //                         ->type('q', $player->player . ' mlb stats')
    //                         ->click('#search_icon') // 使用 Xpath 定位搜索按钮并点击
    //                         ->waitFor('.tpcn');

    //                     $links = $browser->elements('.tpcn a');
    //                     $cites = $browser->elements('.tpcn cite');

    //                     // 等待搜索结果加载完成
    //                     $found = 0;
    //                     // foreach ($links as $key => $link) {
    //                     //     print_r($key . "=>" . $link->getAttribute('href') . "\n");
    //                     // }
    //                     foreach ($cites as $key => $cite) {
    //                         //print_r($cite->getAttribute('href') . "\n");
    //                         if (strpos($cite->getText(), 'www.mlb.com/player')) {
    //                             // print_r($links[$key]->getAttribute('href'));
    //                             //$click_link = 'a[href="' . $links[$key]->getAttribute('href') . '"]';
    //                             //$browser->click($click_link)->waitForReload('https://www.mlb.com/player', 10);
    //                             $browser->visit($links[$key]->getAttribute('href'));

    //                             $currentUrl = $browser->driver->getCurrentURL();
    //                             print_r($currentUrl);
    //                             // preg_match('/(\d+)/', $currentUrl, $matches);
    //                             // if (isset($matches[1])) {
    //                             //     $mlb_id = $matches[1];
    //                             //     $player_info = $this->getPlayerFullname($mlb_id);
    //                             //     if ($player_info['full_name'] == trim($player->player)) {
    //                             //         $player->marked = 6;
    //                             //         $player->full_name = $player_info['full_name'];
    //                             //         $player->first_name = $player_info['first_name'];
    //                             //         $player->last_name = $player_info['last_name'];
    //                             //         $player->middle_name = $player_info['middle_name'];
    //                             //         $player->mlb_player_id = $mlb_id;
    //                             //         $player->save();
    //                             //         $found = 1;
    //                             //         break;
    //                             //     }
    //                             // }
    //                         }
    //                     }
    //                     // if (!$found) {
    //                     //     $player->marked = 5;
    //                     //     $player->save();
    //                     // }
    //                 } catch (Exception $e) {
    //                 }
    //             }
    //         }
    //     );
    // }
    private function searchMlbPlayerId($players)
    {
        $this->browse(
            function (Browser $browser) use ($players) {
                foreach ($players as $key => $player) {
                    try {
                        $links = $browser->visit("https://www.bing.com")
                            ->type('q', $player->player . ' mlb stats')
                            ->click('#search_icon') // 使用 Xpath 定位搜索按钮并点击
                            ->waitFor('.tpcn')
                            ->elements('.tpcn cite');

                        // 等待搜索结果加载完成
                        $found = 0;
                        foreach ($links as $link) {
                            //print_r($link->getAttribute('href') . "\n");

                            if (strpos($link->getText(), 'www.mlb.com/player')) {
                                preg_match('/(\d+)/', $link->getText(), $matches);
                                if (isset($matches[1])) {
                                    $mlb_id = $matches[1];
                                    $player_info = $this->getPlayerFullname($mlb_id);
                                    if ($player_info['full_name'] == trim($player->player)) {
                                        $player->marked = 5;
                                        $player->full_name = $player_info['full_name'];
                                        $player->first_name = $player_info['first_name'];
                                        $player->last_name = $player_info['last_name'];
                                        $player->middle_name = $player_info['middle_name'];
                                        $player->mlb_player_id = $mlb_id;
                                        $player->save();
                                        $found = 1;
                                        break;
                                    }
                                }
                            }
                        }
                        if (!$found) {
                            $player->marked = 6;
                            $player->save();
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        );
    }
}
