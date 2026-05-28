<?php

namespace App\Http\Livewire\Pages\Program;

use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Services\Program\ProgramListServices;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ProgramList extends Component
{
    use WithFileUploads, WithPagination, Notification, LivewireAlert;

    public $file;
    public $filter;
    public $search = '';
    public $showSubForms = null;

    protected $listeners = [
        'updateList' => '$refresh',
    ];

    protected $queryString = [
        'filter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    private $ProgramListServices;

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->ProgramListServices = app(ProgramListServices::class);
    }

    public function loadData()
    {
        return $this->ProgramListServices->getPrograms($this->filter, $this->search);
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->filter = '';
        $this->resetPage(); // 重設分頁到第一頁
    }

    public function deleteProgram($programId)
    {
        $success = $this->ProgramListServices->deleteProgram($programId);

        if ($success) {
            $this->showAlertMessage('success', 'Program deleted successfully.');
            $this->dispatchBrowserEvent('refresh-page');
        } else {
            $this->showAlertMessage('error', 'Program not found.');
        }
    }


    public function updateInsertShortName()
    {
        try {
            $this->ProgramListServices->updateInsertShortNames();
            $this->showAlertMessage('success', 'Updated insert short names successfully.');
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Error updating insert short names: ' . $e->getMessage());
        }
    }

    public function getColorForSP($sp)
    {
        return $this->ProgramListServices->getColorForSP($sp);
    }

    public function clearFilter()
    {
        $this->filter = null;
        $this->resetPage();
    }

    public function filterBySP($sp)
    {
        $this->filter = $sp;
        $this->search = '';
        $this->resetPage();
    }


    public function render()
    {
        return view('livewire.pages.program.program-list', [
            'programs' => $this->loadData(),
            'spList' => $this->ProgramListServices->getAllPrograms()->pluck('sp')->unique()->toArray(),
        ]);
    }

    public function toggleDropdown($programId)
    {
        return redirect()->to(route('program.forms', ['programId' => $programId]));
    }

    public function toggleSubForms($mainProgramId)
    {
        $this->showSubForms = $this->showSubForms === $mainProgramId ? null : $mainProgramId;
    }
}
