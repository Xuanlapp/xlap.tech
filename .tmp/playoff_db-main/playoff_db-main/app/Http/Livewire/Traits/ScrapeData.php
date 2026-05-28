<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;


trait ScrapeData
{
    private function gettingPlayerStats($players): void
    {
        $this->browse(function (Browser $browser) use ($players) {

            foreach ($players as $player) {
                // Grab all data
                try {
                    /*
                        IF Player page is found and it is NOT empty
                    */
                    $data = $this->getPlayerDataFromNba($browser,  $player->nba_player_id);
                } catch (\Exception $e) {
                    try {
                        /*
                            IF Player page is found and it EMPTY
                        */
                        $link = "https://www.nba.com/stats/player/" . $player->nba_player_id . "/career?PerMode=Totals";
                        $browser->visit($link);
                        $browser->waitFor('.NoDataMessage_base__xUA61');
                        $player->marked = 4;
                        $player->save();
                    } catch (\Exception $e) {
                        // $player->marked = 3;
                        // $player->save();
                        $data = 0;
                    }
                }
                $data = $this->handleMultiTeam($data);
                // print_r($data);
                // Save player stat data
                $this->handleData($data, $player);
                $this->getPlayerLatestYear($player);
                print_r("id: " . $player->id . ", " . $player->player . " has been downloaded completed! \n");
            } // end foreach
        });
    }

    private function handleMultiTeam($data)
    {
        $stats = $data['stat'];

        $arr_team = [];
        $popout_indexs = [];
        $target_stat_year = '';
        $tot_index = '';
        $flag = 0;

        foreach ($stats as $index => $stat) {
            if ($stat['team'] == "TOT") {
                if ($flag) {
                    if ($stat['year'] != $target_stat_year) {
                        /* 
                        * Process Data 
                        */
                        // Store team
                        $data['stat'][$tot_index]['team'] = implode("/", $arr_team);
                        // clear flag
                        $flag = 0;
                        // clear Target ID
                        $tot_index = '';
                        // clear Target Year
                        $target_stat_year = '';
                        // clear teamArr
                        $arr_team = [];
                        /* 
                        * Prepare data
                        */
                        // Store target ID
                        $tot_index = $index;
                        // Store Target Year
                        $target_stat_year = $stat['year'];
                        // Set Flag
                        $flag = 1;
                    }
                } else { // flag off
                    if ($stat['year'] != $target_stat_year) {
                        //**** Prepare data
                        // Store index number
                        $tot_index = $index;
                        // Store Target Year
                        $target_stat_year = $stat['year'];
                        // Set Flag
                        $flag = 1;
                    }
                }
            } else {
                if ($flag) {
                    if ($stat['year'] == $target_stat_year) {
                        /* 
                        * Add Team to teamArr
                        */
                        $arr_team[] = $stat['team'];
                        // Store index number, for late to remove
                        $popout_indexs[] = $index;

                        if ($index + 1 == count($stats)) {
                            /* 
                            * Process Data 
                            */
                            // Store team
                            $data['stat'][$tot_index]['team'] = implode("/", $arr_team);
                            // clear flag
                            $flag = 0;
                            // clear Target ID
                            $tot_index = '';
                            // clear Target Year
                            $target_stat_year = '';
                            // clear teamArr
                            $arr_team = [];
                        }
                    } else {
                        /* 
                        * Process Data 
                        */
                        // Store team
                        $data['stat'][$tot_index]['team'] = implode("/", $arr_team);
                        // clear flag
                        $flag = 0;
                        // clear Target ID
                        $tot_index = '';
                        // clear Target Year
                        $target_stat_year = '';
                        // clear teamArr
                        $arr_team = [];
                    }
                }
            }
        }

        // pop out all the index marked
        foreach ($popout_indexs as $index) {
            unset($data['stat'][$index]);
        }
        $data['stat'] = array_values($data['stat']); // 重新索引数组
        // Log::channel('mylog')->info($data['stat']);
        return $data;
    }

    private function handleData($data, $player)
    {
        if ($data) {
            $playerAllStats = $player->stats;
            // Store player stat to stats table
            foreach ($data['stat'] as $stat) {
                if ($playerAllStats->count() > 0) {
                    // Handle when the existed seasons
                    foreach ($playerAllStats as $stat) {
                        if ($stat->year != $stat['year']) {
                            $player->savePlayerStat($stat);
                            break;
                        }
                    }
                } else {
                    // Do not have any existed season
                    $player->savePlayerStat($stat);
                }
            }

            // Store player career
            $careerData = $data['career'];

            $attributes = ['g', 'fg%', 'ft%', '3pm', 'rpg', 'apg', 'stl', 'blk', 'pts', 'ppg'];

            foreach ($attributes as $attribute) {
                $player->{$attribute} = $careerData[$attribute];
            }

            $player->marked = 2;
            $player->save();
        }
    }

    private function getPlayerLatestYear($player)
    {
        if ($player->stats()->count()) {
            $full_latest_year = $player->stats->last()->year;
            $latest_year = explode("-", $full_latest_year)[0] + 1;
            $player->latest_season = $latest_year;
            $player->save();
        }
    }

    /**
     * handle raw data after scrape from site
     *
     * @param  mixed $browser
     * @param  mixed $player_id
     * Use getTotalTableData & getPerGameTableData
     * @return $tableData Array
     **/
    private function getPlayerDataFromNba($browser, $player_id)
    {
        $tableData = json_decode($this->getTotalTableData($browser, $player_id), true);
        $perGameData = json_decode($this->getPerGameTableData($browser, $player_id), true);

        foreach ($tableData['stat'] as $index => $stat) {
            $tableData['stat'][$index]['rpg'] = $perGameData['stat'][$index]['rpg'];
            $tableData['stat'][$index]['apg'] = $perGameData['stat'][$index]['apg'];
            $tableData['stat'][$index]['ppg'] = $perGameData['stat'][$index]['ppg'];
        }

        $tableData['career']['rpg'] = $perGameData['career']['rpg'];
        $tableData['career']['apg'] = $perGameData['career']['apg'];
        $tableData['career']['ppg'] = $perGameData['career']['ppg'];

        // reverse all the stats for example
        // 21-20 21-22 22-23, the recent sort to the bottom
        $tableData['stat'] = array_reverse($tableData['stat']);

        return $tableData;
    }

    /**
     * Get the Total stat of the player from individual player web page
     *
     * @param  mixed $browser
     * @param  mixed $player_id
     * @return void
     */
    private function getTotalTableData($browser, $player_id)
    {
        $link = "https://www.nba.com/stats/player/" . $player_id . "/career?PerMode=Totals";

        $browser->visit($link);
        $browser->waitFor('table');
        $browser->pause(1000); // 等待 1000 毫秒（1 秒）
        return $browser->driver->executeScript('
            let tableData = { "stat": [], "career": {} };
            document.querySelectorAll("table")[0].querySelectorAll("tbody tr").forEach((tr, index, array)=> {
                tableData["stat"].push({
                    "year" : tr.querySelector("td:first-child").innerText.trim(),
                    "team" : tr.querySelector("td:nth-child(2)").innerText.trim(),
                    "g" : tr.querySelector("td:nth-child(4)").innerText.trim(),
                    "fg%" : tr.querySelector("td:nth-child(10)").innerText.trim()/100,
                    "ft%" : tr.querySelector("td:nth-child(16)").innerText.trim()/100,
                    "3pm" : tr.querySelector("td:nth-child(11)").innerText.trim(),
                    "rpg" : "",
                    "apg" : "",
                    "stl" : tr.querySelector("td:nth-child(21)").innerText.trim(),
                    "blk" : tr.querySelector("td:nth-child(22)").innerText.trim(), 
                    "pts" : tr.querySelector("td:nth-child(7)").innerText.trim(), 
                    "ppg" : ""
                });
            });
            let overallData = document.querySelectorAll("table")[0].querySelector("tfoot tr");

            tableData["career"]["g"] = overallData.querySelector("td:nth-child(4)").innerText.trim();
            tableData["career"]["fg%"] = overallData.querySelector("td:nth-child(10)").innerText.trim()/100;
            tableData["career"]["ft%"] = overallData.querySelector("td:nth-child(16)").innerText.trim()/100;
            tableData["career"]["3pm"] = overallData.querySelector("td:nth-child(11)").innerText.trim();
            tableData["career"]["rpg"] = "";
            tableData["career"]["apg"] = "";
            tableData["career"]["stl"] = overallData.querySelector("td:nth-child(21)").innerText.trim();
            tableData["career"]["blk"] = overallData.querySelector("td:nth-child(22)").innerText.trim();
            tableData["career"]["pts"] = overallData.querySelector("td:nth-child(7)").innerText.trim();
            tableData["career"]["ppg"] = "";
            
            return JSON.stringify(tableData);
        ');
    }

    /**
     * Get the Per game stat of a player from individual player web page
     *
     * @param  mixed $browser
     * @param  mixed $player_id
     * @return void
     */
    private function getPerGameTableData($browser, $player_id)
    {
        $link = "https://www.nba.com/stats/player/" . $player_id . "/career?PerMode=PerGame";
        $browser->visit($link);
        $browser->waitFor('table'); // Wait for the table to load
        $browser->pause(1000); // 等待 1000 毫秒（1 秒）
        return $browser->driver->executeScript('
            let tableData = { "stat": [], "career": {} };
            document.querySelectorAll("table")[0].querySelectorAll("tbody tr").forEach((tr, index, array)=> {
                tableData["stat"].push({
                    "rpg" : tr.querySelector("td:nth-child(19)").innerText.trim(),
                    "apg" : tr.querySelector("td:nth-child(20)").innerText.trim(),
                    "ppg" : tr.querySelector("td:nth-child(7)").innerText.trim()
                });
            });
            let overallData = document.querySelectorAll("table")[0].querySelector("tfoot tr");
            tableData["career"]["rpg"] = overallData.querySelector("td:nth-child(19)").innerText.trim();
            tableData["career"]["apg"] = overallData.querySelector("td:nth-child(20)").innerText.trim();
            tableData["career"]["ppg"] = overallData.querySelector("td:nth-child(7)").innerText.trim();
            
            return JSON.stringify(tableData);
        ');
    }
}
