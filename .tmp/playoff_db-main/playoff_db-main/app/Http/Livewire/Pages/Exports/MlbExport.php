<?php

namespace App\Http\Livewire\Pages\Exports;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\MlbExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

//Testing
class MlbExport extends Component
{
    use WithFileUploads, LivewireAlert, Notification;

    private MlbExportService $exportService;
    public $file;

    public $fullShowingData;
    public $partialShowingData;
    public $sumStatus;

    public $selectedItems = [];
    public $selectAll = false;
    public $bulkDisabled = true;
    public $filteredBy = '';
    public $outputTeamNameAbb = false;

    protected $listeners = ['submit' => 'generateEmail'];

    public function boot(MlbExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function render()
    {
        $this->bulkDisabled = count($this->selectedItems) < 1;
        return view('livewire.pages.exports.mlb-export');
    }

    public function generateEmail($data)
    {
        $this->exportService->generateEmailMlb($data, $this->fullShowingData, $this->selectedItems);
    }

    public function warningMessage($message)
    {
        $this->showAlertMessage('warning', $message);
    }

    public function handleFileUpload()
    {
        // Validate the file upload
        $this->validate([
            'file' => 'required|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        // Save the file to the server
        $filePath = $this->file->store('uploads');

        // Merge the Excel Data with MLB Data
        $this->fullShowingData = $this->exportService->prepShowingData($filePath, 'Baseball');
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

    public function outputCsv()
    {
        if (count($this->selectedItems) > 0) {
            if ($this->exportService->checkSelectedItemsStatus($this->fullShowingData, $this->selectedItems)) {
                Request::session()->put('team_name_option', $this->outputTeamNameAbb);

                // 生成临时文件路径
                $filePath = storage_path('app/public/tempOutputFile.csv');
                $this->exportService->handleDataToCsvFile($this->fullShowingData, $this->selectedItems, $filePath);

                // 创建下载链接并存储在 session 中
                $downloadLink = route('download.csv', ['file' => 'tempOutputFile.csv']);
                session()->put('downloadLink', $downloadLink);
                session()->put('showDownloadLink', true);

                $this->showAlertMessage('success', 'CSV file is outputted successfully');
            } else {
                $this->showAlertMessage('warning', 'Selection is included New Players or Deny Players!');
            }
        } else {
            $this->showAlertMessage('warning', 'Please select output player first');
        }
    }

    // public function exportCsvFile()
    // {
    //     // Deal with the title header first
    //     $contents = "firstname, lastname, midname, stat";
    //     Storage::disk('local')->put('file1.csv', $contents);
    //     $contents = 'Hao, Phung, Duc, ' . $this->exportStatWithFormat();
    //     Storage::append('file1.csv', $contents);
    //     $this->showAlertMessage('success', 'CSV File exported');
    //     return Storage::download('file1.csv');
    // }

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
