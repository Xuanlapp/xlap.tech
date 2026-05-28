<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programs extends Model
{
    use HasFactory;

    protected $table = 'programs';
    protected $fillable = [
        'code',
        'year',
        'collection',
        'sp',
        'customer_id',
        'color',
        'auto_build_workflow',
        'outsourced_job',
        'bk_pa_legal',
        'date_name',
        'licensed_bb_product',
        'legal_line',
        'ship'
    ];

    public function getSportHexColor()
    {
        $colors = [
            'FB' => '#85270E',
            'FB1' => '#85270E',
            'EN' => '#940194',
            'ENT' => '#940194',
            'CL' => '#02FCF1',
            'SC' => '#000000',
            'BB' => '#1EBE19',
            'RA' => '#D6C418',
            'RC' => '#F9E421',
            'BK' => '#FF8118',
            'MK' => '#0727FA',
            'Golf' => '#666763',
            'LIV' => '#666763',
            'FIT' => '#F24734',
            'WWE' => '#F24734',
            'DI' => '#D10800',
            'BC' => '#C3C9E8',
            'CBK' => '#008080',
            'CFB' => '#FFA500',
        ];
        return $colors[$this->sp] ?? '#CCCCCC';
    }

    public function mainPrograms()
    {
        return $this->hasMany(Program_forms::class, 'program_id');
    }

    public function subforms()
    {
        return $this->hasMany(Program_subforms::class, 'id');
    }

    public function sportCustomer()
    {
        return $this->belongsTo(sport_customers::class, 'customer_id');
    }

    public function programImage()
    {
        return "images/program_logos/" . preg_replace('/Panini\s*/', '', $this->collection) . ".png";
    }
}
