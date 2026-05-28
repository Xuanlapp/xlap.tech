<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Nba_team;

class logo_nba_versions extends Model
{
    use HasFactory;

    protected $table = 'logo_nba_versions';
    public $timestamps = false;
    protected $fillable = [
        'team_id',
        'begin',
        'end',
        'pri_tc',
        'sec_tc',
        'special_pickup',
        'logo_version_json',
        'secondary',
        'primary',
        'on_white',
        'on_black',
    ];

    // 定義訪問器
    public function getPickupNameAttribute()
    {
        return $this->nba_team ? $this->nba_team->pickup_name : null;
    }

    public function getInitLettersAttribute()
    {
        return $this->nba_team ? $this->nba_team->init_letters : null;
    }

    public function logo_file_name_base()
    {
        return $this->pickup_name . '_' . $this->begin . '_' . $this->init_letters . substr($this->begin, 0, 1) . substr($this->begin, 2);
    }

    public function nba_team()
    {
        return $this->belongsTo(Nba_team::class, 'team_id');
    }

    public function logo_file_name()
    {
        return $this->logo_file_name_base() . 'A1.svg';
    }

    public function logo_version_name($version)
    {
        return $this->logo_file_name_base() . $version . '.svg';
    }

    public function tc_file_name($version)
    {
        return $this->logo_file_name_base() . 'A1_TC' . $version . '.pdf';
    }

    public function wordmark_path()
    {
        return $this->pickup_name . '_' . $this->begin . '_' . $this->init_letters . substr(
            $this->begin,
            0,
            1
        ) . substr($this->begin, 2) . 'N1.pdf';
    }
}
