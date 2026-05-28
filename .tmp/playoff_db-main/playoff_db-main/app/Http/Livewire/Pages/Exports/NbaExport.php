<?php

namespace App\Http\Livewire\Pages\Exports;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\NbaExportService;
use Illuminate\Support\Facades\Log;

class NbaExport extends Component
{
    use WithFileUploads, LivewireAlert, Notification;
    private NbaExportService $exportService;
    public $file;

    public $fullShowingData;
    public $partialShowingData;
    public $sumStatus;

    public $selectedItems = [];
    public $selectAll = false;
    public $bulkDisabled = true;
    public $filteredBy = '';

    protected $listeners = ['submit' => 'generateEmail'];

    public function boot(NbaExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function render()
    {
        $this->bulkDisabled = count($this->selectedItems) < 1;
        return view('livewire.pages.exports.nba-export');
    }

    public function outputCsv()
    {
        if (count($this->selectedItems) > 0) {
            if ($this->exportService->checkSelectedItemsStatus($this->fullShowingData, $this->selectedItems)) {
                $this->exportService->handleDataToCsvFile($this->fullShowingData, $this->selectedItems);
                $filePath =  storage_path('app/public/tempOutputFile.csv');
                $this->showAlertMessage('success', 'CSV file is outputed successfully');
                return response()->download($filePath);
            } else {
                $this->showAlertMessage('warning', 'Selection is included New Players or Deny Players!');
            }
        } else {
            $this->showAlertMessage('warning', 'Please select output player first');
        }
    }

    public function handleFileUpload()
    {
        // Validate the file upload
        $this->validate([
            'file' => 'required|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        // Save the file to the server
        $filePath = $this->file->store('uploads');

        // Merge the Excel Data with NBA Data
        $this->fullShowingData = $this->exportService->prepShowingData($filePath, 'Basketball');
        if ($this->fullShowingData) {
            // in the blade, is using partial showing data,
            // which is solve the filtering issue
            $this->partialShowingData = $this->fullShowingData;

            // get the status count, for filtering player use
            $this->sumStatus = $this->exportService->countStatus($this->fullShowingData);
        } else {
            $this->showAlertMessage('warning', 'Look like you select a wrong file, please check your excel file!');
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
            foreach ($player->show_stat_with_quantity(1) as $year) {
                $data['statOneYear'][] = $year->show_stat();
            }
            $data['career'] = $player->show_career();
        }
        return $data;
    }

    public function generateEmail($data)
    {
        $this->exportService->generateEmailNba($data, $this->fullShowingData, $this->selectedItems);
    }

    public function warningMessage($message)
    {
        $this->showAlertMessage('warning', $message);
    }

    public function updatedFilteredBy($value)
    {
        if ($this->filteredBy !== "") {
            $this->partialShowingData = collect($this->fullShowingData)->filter(function ($item) {
                return $item['status'] == $this->filteredBy;
            });
        } else {
            $this->partialShowingData = $this->fullShowingData;
        }
    }

    public function selectBetween()
    {
        if (count($this->selectedItems) !== 2) {
            if (count($this->selectedItems) < 2) {
                $this->showAlertMessage('warning', 'Please Select more than ONE items!');
            } else {
                $this->showAlertMessage('warning', 'Please Select less than TWO items!');
            }
        } else {
            $first = $this->selectedItems[0];
            $last = $this->selectedItems[1];
            $this->selectedItems = range($first, $last);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = collect($this->partialShowingData)->pluck('Card #');
            //$this->selectedItems = range(0, $this->dataCount);
        } else {
            $this->selectedItems = [];
        }
    }
}
