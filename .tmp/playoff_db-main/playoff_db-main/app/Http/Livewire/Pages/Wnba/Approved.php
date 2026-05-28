<?php

namespace App\Http\Livewire\Pages\Wnba;

use App\Models\Panini_wnba_player;
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

    public $search_player = '';
    public $toggle = 0;

    protected $listeners = [
        'updateList' => '$refresh',
        'updateSelectedItems' => 'fixSelectedItems'
    ];

    public function render()
    {
        return view('livewire.pages.wnba.approved', [
            'players' => $this->loadData()
        ]);
    }

    public function loadData()
    {
        $query = Panini_wnba_player::query()->where('marked', 1);
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

}
