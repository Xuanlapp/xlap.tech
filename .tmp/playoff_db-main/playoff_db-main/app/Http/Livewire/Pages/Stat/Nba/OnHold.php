<?php

namespace App\Http\Livewire\Pages\Stat\Nba;

use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use Livewire\Component;
use App\Services\NbaFetchDataService;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class OnHold extends Component
{
    use LivewireAlert, Notification;

    public $selected_team = '';
    public $search_player = '';
    public $selected_team_object = '';
    public $toggle = 0;
    public $kind = "NBA";
    private NbaFetchDataService $nbaFetchService;

    public function boot(NbaFetchDataService $nbaFetch)
    {
        $this->nbaFetchService = $nbaFetch;
    }

    protected $listeners = [
        'updateList' => '$refresh',
        'selectedTeamItem' => 'selectedTeamItem'
    ];

    public function fetchData($player_id)
    {
        $player = Panini_nba_player::find($player_id);
        $this->nbaFetchService->saveData($player);
    }

    public function render()
    {
        return view('livewire.pages.nba.on-hold', [
            'players' => $this->loadData(),
            'team_kind' => Nba_team::get()->groupBy('kind')
        ]);
    }

    public function loadData()
    {
        $query = Panini_nba_player::query()->where('marked', 1);
        // $query = $query->where('team_name', "Birmingham Squadron");
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

    public function downloadStat($player_id)
    {
        $player = Panini_nba_player::where('id', $player_id)->first();
        // dd($player);
        if ($this->nbaFetchService->saveData($player, "")) {
            $this->showAlertMessage('success', 'Updated success!');
            $this->dispatchBrowserEvent('refresh-page');

        } else {
            $this->showAlertMessage('warning', 'Something wrong with the NBA ID!');
        }
    }
}
