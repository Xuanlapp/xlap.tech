<?php

namespace App\Http\Livewire\Pages\Stat\Nba;

use App\Models\Nba_team;
use App\Models\Panini_nba_player;
use Livewire\Component;

class NbaPlayerList extends Component
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
        return view('livewire.pages.nba.nba-player-list', [
            'players' => $this->loadData(),
            'team_kind' => Nba_team::get()->groupBy('kind')
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

    public function sendingData()
    {
        $handle = curl_init('https://eour6lgg70s8p5m.m.pipedream.net');

        $data = [
            'key' => 'value'
        ];

        $encodedData = json_encode($data);

        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($handle);

        // $name = "LeBron James";
        // $sku = "Bk23-PI-0216-53";
        // $price = "1099.99";
        // $pan_offer_start_date = "5/17/2023";
        // $pan_offer_end_date = "5/20/2023 05:00 PM";
        // $sport_name = "sport_name";
        // $year = "2023";

        // $pathToImage1 = public_path('/Users/princeofforest/Downloads/images.jpg');
        // $pathToImage2 = public_path('/Users/princeofforest/Downloads/images.jpg');

        // $response = Http::withHeaders([
        //     'Cookie' => '_cfuvid-NUx8]Nja4_NukNH9.bp.ODz/vUh.wRG5ZNQdsyNkp4A-1681410934994-0-604800000'
        // ])->attach('images[]', $pathToImage1)
        //     ->attach('images[]', $pathToImage2)
        //     ->post('https://support.paniniamerica.net/instant-data', [
        //         'name' => $name,
        //         'sku' => $sku,
        //         'price' => $price,
        //         'pan_offer_start_date' => $pan_offer_start_date,
        //         'pan_offer_end_date' => $pan_offer_end_date,
        //         'sport_name' => $sport_name,
        //         'year' => $year,
        //     ]);
        // $response->throw();
        // $responseContent = $response->body();

        // dd($responseContent);
    }
}
