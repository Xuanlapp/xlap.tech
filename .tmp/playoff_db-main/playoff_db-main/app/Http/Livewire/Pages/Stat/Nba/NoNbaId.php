<?php

namespace App\Http\Livewire\Pages\Stat\Nba;

use App\Models\Panini_nba_player;
use Livewire\Component;
use App\Models\Nba_team;

class NoNbaId extends Component
{
    public $selected_team = '';
    public $search_player = '';
    public $selected_team_object = '';
    public $toggle = 0;
    public $kind = "NBA";

    public function render()
    {
        return view('livewire.pages.nba.no-nba-id', [
            'players' => $this->loadData(),
            'team_kind' => Nba_team::get()->groupBy('kind'),
        ]);
    }

    public function loadData()
    {
        $query = Panini_nba_player::query()->where('marked', 2);
        // $query = $query->where('team_name', "Birmingham Squadron");
        if ($this->selected_team !== '') {
            $query = $query->where('team_name', $this->selected_team);
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        $query = $query->orderBy('player');
        return $query = $query->paginate($perPage = 50);
    }
}
