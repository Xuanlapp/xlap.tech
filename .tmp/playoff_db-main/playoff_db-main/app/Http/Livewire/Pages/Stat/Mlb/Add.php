<?php

namespace App\Http\Livewire\Pages\Stat\Mlb;

use App\Models\panini_mlb_player;
use App\Models\mlb_team;
use Livewire\Component;
use Livewire\WithPagination;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;

// use App\Http\Livewire\Traits\MlbDownloadData;
// use Illuminate\Support\Facades\Storage;

class Add extends Component
{
    use WithPagination, LivewireAlert, Notification;

    protected $listeners = [
        'reload' => 'loadData',
        'updateList' => '$refresh',
        'updateSelectedItems' => 'fixSelectedItems',
        'selectedTeamItem' => 'selectedTeamItem'
    ];
    public $sport = 'mlb';
    public $kind = 'MLB';
    public $selected_team = '';
    public $active = 'both';
    public $search_player = '';
    public $detail = false;

    public $selectedItems = [];
    public $selectAll = false;
    public $bulkDisabled = true;

    public function render()
    {
        $className = "App\Models\\" . $this->sport . "_team";

        return view("livewire.pages.{$this->sport}.lap", [
        ]);
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
        } else {
            $this->selected_team = "";
        }
    }

    public function loadData()
    {
        $query = panini_mlb_player::query();

        if ($this->active == 'active') {
            $query = $query->where('active', 1);
        }
        if ($this->active == 'retired') {
            $query = $query->where('active', 0);
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');

        }
        if ($this->selected_team !== '') {
            $query = $query->where('last_played_team', $this->selected_team);
        }
        $query = $query->orderBy('last_name');

        return $query->paginate($perPage = 100);
    }

    /**
     * updatedSelectAll This will be trigger when $selectAll valualble is uopdated
     *
     * @param mixed $value
     * @return void
     */
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->loadData()->pluck('id');
        } else {
            $this->selectedItems = [];
        }
    }

    public function fixSelectedItems($removeItemList)
    {
        $originalArr = $this->selectedItems->toArray();
        $this->selectedItems = array_diff($originalArr, $removeItemList);
    }
}
