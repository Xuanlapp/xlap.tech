<?php

namespace App\Http\Livewire\Pages\Logo;

use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use App\Models\logo_nba_versions;
use Livewire\Component;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LogoBasketball extends Component
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;


    protected $paginationTheme = 'tailwind';
    public $selectedItems = [];
    public $selectAll = false;

    public $search = '';
    public $toggle = 0;
    public $kind = "";

    protected $listeners = [
        'updateList' => '$refresh',
        'selectedTeamItem' => 'selectedTeamItem',
        'updateSelectedItems' => 'fixSelectedItems'
    ];

    public function resetSelection()
    {
        $this->selectedLogoId = null;
        $this->selectedLogoDetails = null;
    }


    public function render()
    {
        return view('livewire.pages.logo.basketball.logo-basketball', [
            'teams' => $this->loadData(),
            'team_kind' => Nba_team::where('kind', '!=', 'college')
                ->where('kind', '!=', 'international')
                ->get()->groupBy('kind')
        ]);
    }

    public $selectedLogoId = null;
    public $selectedLogoDetails = null;

    public function selectLogo($teamId)
    {
        if ($teamId == null) {
            $this->showAlertMessage('error', 'No team id!');
        } else {
            $this->selectedLogoId = $teamId;
            $this->selectedLogoDetails = logo_nba_versions::with('nba_team')->where('team_id', $teamId
            )->orderBy('begin', 'desc')->get();
            $this->emit('scrollToTop');
        }
    }

    public function loadData()
    {
        $query = Nba_team::query()
            ->where('kind', '!=', 'college')
            ->where('kind', '!=', 'international');
        if ($this->kind !== '') {
            $query->where('kind', $this->kind);
        }

        if ($this->search !== '') {
            $query->where('team_name', 'like', '%' . $this->search . '%');
        }
        $query = $query->orderBy('id', 'asc');
        return $query->get();
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

    public function updatedSearch($value)
    {
        $this->kind = "";
        $this->selectedLogoId = null;
        $this->selectedLogoDetails = null;
    }

    public function updatedselectedLogoId($value)
    {

        $this->selectedLogoId = $value;
        $this->selectedLogoDetails = null;
    }

    public function updatedKind($value)
    {
        $this->selectedLogoId = null;
        $this->selectedLogoDetails = null;
    }

    public function fixSelectedItems($removeItemList)
    {
        $originalArr = $this->selectedItems;
        $this->selectedItems = array_diff($originalArr, $removeItemList);
    }
}
