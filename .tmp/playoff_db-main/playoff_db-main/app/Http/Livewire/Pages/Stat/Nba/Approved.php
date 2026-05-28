<?php

namespace App\Http\Livewire\Pages\Stat\Nba;

use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use Livewire\Component;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Approved extends Component
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;


    protected $paginationTheme = 'tailwind';
    public $selectedItems = [];
    public $selectAll = false;

    public $selected_team = '';
    public $search_player = '';
    public $selected_team_object = '';
    public $toggle = 0;
    public $kind = "NBA";

    protected $listeners = [
        'updateList' => '$refresh',
        'selectedTeamItem' => 'selectedTeamItem',
        'updateSelectedItems' => 'fixSelectedItems'
    ];

    public function render()
    {
        return view('livewire.pages.nba.approved', [
            'players' => $this->loadData(),
            'team_kind' => Nba_team::get()->groupBy('kind')
        ]);
    }

    public function loadData()
    {

        $query = Panini_nba_player::query()->where('marked', 4);
        if ($this->selected_team !== '') {
            $query = $query->where('team_name', $this->selected_team);
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        $query = $query->orderBy('player');
        return $query = $query->paginate($perPage = 100);
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

    /**
     * This trigger by emit
     *
     * @param mixed $item
     * @return void
     */
    public function selectedTeamItem($item)
    {
        if ($item) {
            $this->selected_team = $item;
            $this->selected_team_object = Nba_team::where('team_name', $item)->first();
        } else {
            $this->selected_team = "";
        }
    }

    /**
     * updatedSelectAll This will be trigger when $selectAll valualble is updated
     *
     * @param mixed $value
     * @return void
     */
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->loadData()->pluck('id')->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function fixSelectedItems($removeItemList)
    {
        $originalArr = $this->selectedItems;
        $this->selectedItems = array_diff($originalArr, $removeItemList);
    }
}
