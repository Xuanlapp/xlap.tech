<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class color_name extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'color_name';
    protected $fillable = [
        'customer_name',
        'color_name',
        'sure',
        'change_color_to',
        'to_db'

    ];

    public function programs()
    {
        return $this->hasMany(Programs::class, 'customer_id');
    }
}
