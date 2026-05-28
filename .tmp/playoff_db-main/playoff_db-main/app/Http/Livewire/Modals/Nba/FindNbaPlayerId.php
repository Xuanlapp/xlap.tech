<?php

namespace App\Http\Livewire\Modals\Nba;

use LivewireUI\Modal\ModalComponent;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use function Symfony\Component\String\u;
use App\Models\Panini_nba_player;

class FindNbaPlayerId extends ModalComponent
{
    use Notification, LivewireAlert;

    public function render()
    {
        return view('livewire.modals.nba.find-nba-player-id');
    }

    public function mount()
    {

    }


    public function FindNbaPlayerId()
    {
        $players = Panini_nba_player::where('marked', 2)
            ->whereNull('nba_player_id')
            ->get();
        $updatedCount = 0;

        foreach ($players as $player) {
            $playerName = $player->player;
            $query = rawurlencode($playerName . ' nba player id');
            $url = "https://www.google.com/search?q=$query";
//            dd($url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            if (preg_match('/https:\/\/www\.nba\.com\/player\/(\d+)/', $response, $matches)) {
                $nbaPlayerId = $matches[1];
                $player->nba_player_id = $nbaPlayerId;
                $player->save();
                $updatedCount++;
            }
        }
        return "Đã cập nhật $updatedCount cầu thủ với nba_player_id mới.";

    }


    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return 'lg';
    }
}
