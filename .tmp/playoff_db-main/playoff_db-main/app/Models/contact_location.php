<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contact_location extends Model
{
    use HasFactory;

    protected $table = 'contact_locations';

    protected $fillable = ['location_name', 'address'];
    public $timestamps = false;

    public function departments()
    {
        return $this->hasMany(contact_department::class, 'id');
    }
}
