<?php

namespace App\Http\Livewire\Modals\Wnba;

use LivewireUI\Modal\ModalComponent;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use function Symfony\Component\String\u;
use App\Models\Panini_wnba_player;

class FindWnbaPlayerId extends ModalComponent
{
    use Notification, LivewireAlert;

    public function render()
    {
        return view('livewire.modals.wnba.find-wnba-player-id');
    }

    public function mount()
    {

    }


    public function FindWnbaPlayerId()
    {
        $players = Panini_wnba_player::where('marked', 2)
            ->whereNull('wnba_player_id')
            ->get();
        $updatedCount = 0;

        foreach ($players as $player) {
            $playerName = $player->player;
            $query = rawurlencode($playerName . ' wnba player id');
            $url = "https://www.google.com/search?q=$query";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            if (preg_match('/https:\/\/www\.wnba\.com\/player\/(\d+)/', $response, $matches)) {
                $wnbaPlayerId = $matches[1];
                $player->wnba_player_id = $wnbaPlayerId;
                $player->save();
                $updatedCount++;
            }
        }
        return "Đã cập nhật $updatedCount cầu thủ với wnba_player_id mới.";
    }


    /**
     * Supported: 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'
     */
    public static function modalMaxWidth(): string
    {
        return 'lg';
    }
}
