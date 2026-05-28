<?php

namespace App\Http\Livewire\Pages\Wnba;

use App\Models\Panini_wnba_player;
use App\Imports\WnbaPlayersImport;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithPagination;   

class NewPlayer extends Component
{
    use WithFileUploads;
    use WithPagination;
    
    public $selected_team = '';
    public $search_player = '';
    public $selected_team_object = '';
    public $toggle = 0;
    public $excel_file;
    public $importing = false;

    protected $listeners = [
        'updateList' => '$refresh'
    ];

    public function render()
    {
        return view('livewire.pages.wnba.new-player', [
            'players' => $this->loadData(),
        ]);
    }

    public function loadData()
    {
        $query = Panini_wnba_player::query()->where('marked', 0);
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        $query = $query->orderBy('player');
        return $query = $query->paginate($perPage = 100);
    }
    
    public function importExcel()
    {
        $this->validate([
            'excel_file' => 'required|mimes:xlsx,xls|max:10240', // 最大 10MB
        ]);

        try {
            $this->importing = true;
            
            // 使用 PhpSpreadsheet 來讀取所有工作表
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->excel_file->getRealPath());
            
            $importedCount = 0;
            $totalPlayers = 0;
            
            \Log::info("Starting Excel import with " . count($spreadsheet->getAllSheets()) . " sheets");
            
            // 逐一處理每個工作表
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetName = $worksheet->getTitle();
                
                \Log::info("Processing sheet: {$sheetName}");
                
                try {
                    // 將工作表資料轉換為 Collection
                    $sheetData = [];
                    $highestRow = $worksheet->getHighestRow();
                    $highestColumn = $worksheet->getHighestColumn();
                    
                    \Log::info("Sheet {$sheetName} has {$highestRow} rows and {$highestColumn} columns");
                    
                    for ($row = 1; $row <= $highestRow; $row++) {
                        $rowData = [];
                        for ($col = 'A'; $col <= $highestColumn; $col++) {
                            $cellValue = $worksheet->getCell($col . $row)->getValue();
                            $rowData[] = $cellValue;
                        }
                        $sheetData[] = collect($rowData); // 每一行都轉換為 Collection
                        
                        // 記錄前5行的資料以便調試
                        if ($row <= 5) {
                            \Log::info("Row {$row}: " . json_encode($rowData));
                        }
                    }
                    
                    // 建立 Collection 並處理
                    $collection = collect($sheetData);
                    $import = new WnbaPlayersImport($sheetName);
                    $import->collection($collection);
                    
                    $importedCount++;
                    
                } catch (\Exception $e) {
                    // 記錄錯誤但繼續處理其他工作表
                    \Log::error("Failed to import sheet '{$sheetName}': " . $e->getMessage());
                    \Log::error($e->getTraceAsString());
                }
            }
            
            // 計算總共匯入的球員數量
            $totalPlayers = Panini_wnba_player::count();
            
            // 重新整理資料
            $this->emit('updateList');
            $this->reset(['excel_file']);
            
            session()->flash('message', "Excel 檔案匯入成功！處理了 {$importedCount} 個工作表，資料庫中共有 {$totalPlayers} 名球員。");
            
        } catch (\Exception $e) {
            \Log::error('Excel import failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            session()->flash('error', '匯入失敗：' . $e->getMessage());
        } finally {
            $this->importing = false;
        }
    }

    /**
     * This is so important, after selected option
     * Select2 still active
     *
     * @return void
     */
    public function hydrate()
    {
        $this->emit('select2');
    }
}
