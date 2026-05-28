<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source_player extends Model
{
    use HasFactory;

    public function player_img_url()
    {
        return "https://img.mlbstatic.com/mlb-photos/image/upload/d_people:generic:headshot:67:current.png/w_639,q_auto:best/v1/people/" . $this->mlb_player_id . "/headshot/67/current";
    }
}
