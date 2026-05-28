<?php

namespace App\Http\Livewire\Pages\Stat\Nba;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Livewire\Traits\Notification;
use App\Models\nba_player_id_raw;
use App\Models\Panini_nba_player;
use App\Models\Nba_team;

class PreApproval extends Component
{
    public $selected_team = '';
    public $status = 'both';
    public $search_player = '';

    public function render()
    {
        return view('livewire.pages.nba.pre-approval', [
            'players' => $this->loadData(),
            'teams' => Nba_team::all()
        ]);
    }

    public function loadData()
    {
        $query = Panini_nba_player::where('approval_status', 1);
        if ($this->selected_team !== '') {
            $query = $query->where('team_name', $this->selected_team);
        }
        if ($this->status != "both") {
            if ($this->status == '1') {
                $query = $query->where('status', 1);
            }
            if ($this->status == '0') {
                $query = $query->where('status', 0);
            }
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        return $query = $query->paginate($perPage = 100);
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
}
