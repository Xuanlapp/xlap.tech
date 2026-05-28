<?php

namespace App\Http\Livewire\Pages\Mlb;

use Livewire\Component;
use App\Models\panini_mlb_player;
use App\Http\Livewire\Traits\MlbDownloadData;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;


class NewPlayer extends Component
{
    use MlbDownloadData, LivewireAlert, Notification;
    public $search_player = '';

    protected $listeners = [
        'updateList' => '$refresh',
        'confirm' => 'removePlayer'
    ];

    public function loadData()
    {
        $query = panini_mlb_player::where('marked', 0);
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        $query = $query->orderBy('player');
        return $query->paginate($perPage = 100);
    }

    public function render()
    {
        return view('livewire.pages.mlb.new-player', [
            'players' => $this->loadData(),
        ]);
    }

    public function removePlayer($id)
    {
        panini_mlb_player::find($id)->delete();
        $this->showAlertMessage('success', 'The player has been removed!');
    }

    public function putOnHold($id)
    {
        $player = panini_mlb_player::find($id);
        $player->marked = 1;
        $player->save();
        $this->showAlertMessage('success', 'The player has been put onhold!');
    }
}
