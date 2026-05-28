<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sport_customers extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'sport_customers';
    protected $fillable = [
        'customer_name',
    ];

    public function programs()
    {
        return $this->hasMany(Programs::class, 'customer_id');
    }
}
