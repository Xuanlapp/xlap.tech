<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithPagination;


class ProgramSubFormsList extends Component
{
    use Notification, LivewireAlert, WithPagination;

    public $sortOption = 'form';

    public $programId, $Programs = [], $showDropdown = null, $Showsub_forms = null, $cutSubForm, $totalForms = 0, $totalInserts = 0;
    public $search = '';
    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount($programId)
    {
        $this->totalForms = Program_forms::where('program_id', $this->programId)->count();
        $this->totalInserts = Program_subforms::where('program_id', $this->programId)->count();
        $this->programId = $programId;
        $this->Programs = Programs::where('id', $this->programId)->first();
//        $this->mainPrograms = Program_subforms::where('program_id', $this->programId)
//            ->orderBy('id')->get();
    }

    public function setSortOrder($order)
    {
        $this->sortOrder = $order; // Cập nhật thứ tự sắp xếp
    }


    public function loadData()
    {

        $query = Program_subforms::where('program_id', $this->programId);

        // Tìm kiếm theo giá trị nhập vào trường search
        if ($this->search !== '') {
            $query->where('insert_name', 'like', '%' . $this->search . '%');
        }
        if ($this->sortOption === 'insert_name') {
            $query->orderBy('insert_name', 'asc')->orderBy('id', 'asc');
        }

        // Phân trang
        return $query->orderBy('id', 'asc')->get();
    }


    public function render()
    {
        return view('livewire.pages.program.program-sub-forms-list', [
            'ProgramsSubForm' => $this->loadData(),
            'programs' => $this->Programs
        ]);
    }
}
