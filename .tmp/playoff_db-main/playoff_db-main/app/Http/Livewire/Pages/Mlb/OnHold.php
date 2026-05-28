<?php

namespace App\Http\Livewire\Pages\Mlb;

use App\Models\panini_mlb_player;
use App\Models\mlb_team;
use Livewire\Component;
use Livewire\WithPagination;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Livewire\Traits\MlbDownloadData;

class OnHold extends Component
{
    use WithPagination, MlbDownloadData, LivewireAlert, Notification;

    protected $listeners = [
        'updateList' => '$refresh',
        'selectedTeamItem' => 'selectedTeamItem'
    ];

    public $kind = 'MLB';
    public $selected_team = '';
    public $status = 'both';
    public $search_player = '';

    public $selectedItems = [];
    public $selectAll = false;
    public $bulkDisabled = true;

    public function loadData()
    {
        $query = panini_mlb_player::where('marked', 1);

        if ($this->selected_team !== '') {
            $query = $query->where('panini_team', $this->panini_team);
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        $query = $query->orderBy('player');

        return $query->paginate($perPage = 100);
    }

    public function render()
    {
        $this->bulkDisabled = count($this->selectedItems) < 1;
        return view('livewire.pages.mlb.on-hold', [
            'players' => $this->loadData(),
            'team_kind' => mlb_team::get()->groupBy('kind')
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
     * @param  mixed $item
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

    public function changeMlbPlayerId($id)
    {
        $this->open_modal('forms.stat.edit-player-id', $id);
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->loadData()->pluck('id');
        } else {
            $this->selectedItems = [];
        }
    }

    public function approveChecked()
    {
        if ($this->selectedItems) {
            foreach ($this->selectedItems as $id) {
                $player = panini_mlb_player::where('id', $id)->first();
                $player->status = 3;
                $player->save();
            }
            $this->selectedItems = [];
            $this->showAlertMessage('success', 'Approved successfully');
        } else {
            $this->showAlertMessage('warning', 'No item is checked');
        }
    }

    public function approved($id)
    {
        $player = panini_mlb_player::find($id);
        $player->status = 3;
        $player->save();
    }

    public function getPaniniTeams()
    {
        $teams = [];
        foreach (panini_mlb_player::all() as $player) {
            $teams[] = $player->panini_team;
        }
        $teams_array = array_unique($teams);
        sort($teams_array);
        foreach ($teams_array as $team) {
            MlbPaniniTeam::create(['team_name' => $team]);
        }
    }

    public function downloadData($player_id)
    {
        $player = panini_mlb_player::where('player_id', $player_id)->first();
        $data = $this->loadApiData($player_id);

        //MATCHING FULL NAME
        if ($this->matching_full_name($data, $player['panini_full_name'])) {
            $data['name_match'] = true;
            $data['last_name_match'] = true;
        }

        //MATCHING LAST NAME
        if (!$data['name_match']) {
            $data['last_name_match'] = $this->matching_last_name($data, $player['panini_full_name']);
        }
        $data['name_match'] = $this->matching_full_name($data, $player['panini_full_name']);

        //Compare all played team match
        $data['any_team_match'] = $this->compare_all_team_match($data['teams_played'], $player->panini_team);

        $data['team_id'] = $this->get_team_id($player->panini_team);

        dd($data);
    }
}
