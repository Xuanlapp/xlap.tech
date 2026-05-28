<?php

namespace App\Http\Livewire\Pages\Stat\Wnba;

use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use App\Models\Panini_wnba_player;
use Livewire\Component;

class WnbaNewPlayer extends Component
{
    public $selected_team = '';
    public $search_player = '';
    public $selected_team_object = '';
    public $toggle = 0;
    public $kind = "NBA";

    protected $listeners = [
        'updateList' => '$refresh',
        'selectedTeamItem' => 'selectedTeamItem'
    ];

    public function render()
    {

        return view('livewire.pages.wnba.wnba-new-player', [
            'players' => $this->loadData(),
            'team_kind' => Nba_team::get()->groupBy('kind')
        ]);
    }

    public function loadData()
    {
        $query = Panini_wnba_player::query()->where('marked', 0);
        if ($this->selected_team !== '') {
            $query = $query->where('team', $this->selected_team);
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
}
