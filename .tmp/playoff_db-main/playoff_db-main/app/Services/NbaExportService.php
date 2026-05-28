<?php

namespace App\Services;

use League\Csv\Writer;
use Illuminate\Support\Str;
use App\Models\panini_nba_player;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class NbaExportService
{
    public function handleDataToCsvFile($fullShowingData, $selectedItems)
    {
        /*
        Handle the title header (this function comes from trait)
        */
        // remove file1.csv first
        $filePath = storage_path('app/public/tempOutputFile.csv');
        if (Storage::disk('local')->exists('public/tempOutputFile.csv')) {
            Storage::disk('local')->delete('public/tempOutputFile.csv');
        }
        Storage::disk('local')->put('public/tempOutputFile.csv', '');

        $content = $this->csvTitle($fullShowingData, $selectedItems);

        $csv = Writer::createFromPath($filePath, 'w+');
        $csv->insertOne($content);
        foreach (collect($selectedItems)->sort() as $card) {
            $result = array_filter($fullShowingData, function ($element) use ($card) {
                $strlen = strlen($element['Height']);
                return $element['Card #'] == $card;
            });
            $result = collect($result)->first();
            $strlen = Str::length($result['Height']);

            // $this take care the Height problem with extra space at the begin and end 
            $result['Height'] = substr(substr($result['Height'], 2), 0, $strlen - 2);

            $content = $this->csvBody($result);
            $csv->insertOne($content);
        }
    }

    private function csvBody($row)
    {
        /*    
            Handle the body content
        */
        // $problemCol = 'Height';
        if ($row['statTitle'] !== null) {
            if ($row['status']  == 4) {
                $output_stats = $this->exportStatWithFormat($row['PlayerID']);
            } else {
                $output_stats = '';
            }
        } else {
            $output_stats = '';
        }

        // we don't want these column export to csv file
        $remove_items_array = ['NbaId', 'status', 'statTitle', 'statOneYear', 'career'];

        // unset($output_stats['MlbId']);
        foreach ($remove_items_array as $value) {
            unset($row[$value]);
        }

        $row['College'] = '"' . $row['College'] . '"';

        $row['stat'] = $output_stats;

        return $row;
    }

    /**
     * export Stat With Format to fulfill indesign
     *
     * @return string
     */
    private function exportStatWithFormat($player_id)
    {
        $output_str = '';
        $player = panini_nba_player::where('panini_id', $player_id)->first();
        // dd($player_id);
        $output_str .= "Year \t Team \t" . implode("\t", $player->show_stat_title()) . " ";
        // dd($player->show_stat_with_quantity(1));
        foreach ($player->show_stat_with_quantity(1) as $year) {
            $output_str .= implode("\t", $year) . " ";
        }
        $output_str .= "Career\t\t" . implode("\t", $player->show_career());
        // dd($player->show_career());
        return $output_str;
    }

    public function checkSelectedItemsStatus($fullShowingData, $selectedItems)
    {
        $selected_players = collect($fullShowingData)->whereIn('Card #', $selectedItems);
        $passed = true;
        foreach ($selected_players as $player) {
            if ($player['status'] != 4) {
                $passed = false;
                break;
            }
        }
        return $passed;
    }

    public function csvTitle($fullShowingData, $selectedItems)
    {
        $selected_players = collect($fullShowingData)->whereIn('Card #', $selectedItems);
        /*    
            Handle the title header
        */
        // we don't want these column export to csv file
        $remove_items_array = ['NbaId', 'status', 'statTitle', 'statOneYear', 'career'];
        // extract the title
        $title_header_array = [];
        foreach (collect($fullShowingData)->first() as $key => $value) {
            if (!in_array($key, $remove_items_array, true)) {
                $title_header_array[] = $key;
            }
        }
        // add extra columns here for the header title
        // add formatted stat column
        array_push($title_header_array, "stat");

        //$contents = implode(", ", $title_header_array);
        $contents = $title_header_array;

        return $contents;
    }


    /**
     * This take the excel data, handle the DOB column that causes issue
     *
     * @param  mixed $data excel data
     * @return void
     */
    public function handleExcelData($data)
    {
        $newData = [];
        // take out all the empty lines
        foreach ($data as $row) {
            // TODO: Add your code to process the data
            if ($row[1] != null) {
                $newData[] = $row;
            }
        }
        $modifiedData = [];

        foreach ($newData as $key => $row) {
            if ($key != 0) {
                foreach ($row as $itemKey => $item) {
                    $modifiedData[$key][$newData[0][$itemKey]] = $item;
                }
            }
        }

        //handle the date format for D.O.B. field
        foreach ($modifiedData as $key => $row) {
            if ($key !== 0) {
                // dd($row[$dob_column_num]);
                $modifiedData[$key]['D.O.B.'] = date(
                    'm/d/y',
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(
                        (int)$row['D.O.B.']
                    )
                );
            }
        }

        return $modifiedData;
    }

    /**
     * prepShowingData
     *
     * @param  string $filePath
     * @return object $data
     */
    public function prepShowingData($filePath, $sport)
    {
        $excelData =  Excel::toArray([], storage_path('app/' . $filePath))[0];

        // after load all the data, just remove the file in storage space
        Storage::disk('local')->delete($filePath);

        // look for what kind of sport for this excel file
        $sportForThisExport = explode(' ', $excelData[2][0])[count(explode(' ', $excelData[2][0])) - 1];
        if ($sportForThisExport == $sport) {
            $data = $this->handleExcelData($excelData);
            foreach ($data as $key => $row) {
                // Use the Key of the excelData to get the player Model
                $player = panini_nba_player::where('panini_id', $row['PlayerID'])->get()->first();

                if ($player) {
                    // check for the player marked
                    foreach ($this->mergeDataFromDB($player) as $dataKey => $value) {
                        $data[$key][$dataKey] = $value;
                    }
                } else {
                    // If the player was not found
                    $newPlayerData = [
                        'panini_id' => $row['PlayerID'],
                        'player' => $row['Player'],
                        'panini_team' => $row['Team'],
                        'marked' => 0
                    ];
                    // Create a new player 
                    panini_nba_player::create($newPlayerData);
                }
            }
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Merge data from Database
     *
     * @param  mixed $player
     * @return void
     */
    public function mergeDataFromDB($player)
    {
        // var_dump($player);
        $data['NbaId'] = $player->nba_player_id;
        $data['status'] = $player->marked;
        if ($player->stats->count() == 0) {
            $data['statTitle'] = null;
            $data['statOneYear'] = null;
            $data['career'] = null;
        } elseif ($player->stats) {
            $data['statTitle'] = $player->show_stat_title();
            $data['statOneYear'] = $player->show_stat_with_quantity(1);
            $data['career'] = $player->show_career();
        }
        return $data;
    }

    public function countStatus($fullShowingData)
    {
        $groupedCollect = collect($fullShowingData)->groupBy('status');
        $statusArr = [0 => 'New Player', 1 => 'No Matching', 4 => 'Approved'];
        $result = [];
        foreach ($groupedCollect as $key => $value) {
            $result[$key] = [
                'name' => $statusArr[$key],
                'count' => count($value)
            ];
        }
        return $result;
    }

    /**
     * Create an Email report specific for NBA
     *
     * @param  mixed $data
     * @param  mixed $fullShowingData
     * @param  mixed $selectedItems
     * @return void
     */
    public function generateEmailNba($data, $fullShowingData, $selectedItems)
    {
        $selected_players = collect($fullShowingData)->whereIn('Card #', $selectedItems);
        $newLine = "%0D%0A";
        $recipient = 'hphung@paniniamerica.net';
        $subject = 'Job: ' . $data['job'];
        $body = '';
        $body .= 'Job Number: ' . $data['job'] . $newLine;
        $body .= 'Comment: ' . $data['comment'] . $newLine;
        $body .= $newLine;
        $body .= 'Player list with issue' . $newLine;

        foreach ($selected_players as $key => $value) {
            switch ($value['status']) {
                case 0:
                    $status = "New";
                    break;
                case 1:
                    $status = "On-hold";
                    break;
                case 4:
                    $status = "Approved";
                    break;
            }
            $body .= 'Card #: ' . $value['Card #'] .
                ' | Player: ' . $value['Player'] .
                ' | Panini ID: ' . $value['PlayerID'] .
                ' | Status: ' . $status . $newLine;
        }

        $url = 'mailto:' . $recipient . '?subject=' . $subject . '&body=' . $body;
        // Log::channel('mylog')->info($url);
        return redirect()->away($url);
    }
}
