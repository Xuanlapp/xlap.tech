<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contact_department extends Model
{
    use HasFactory;

    protected $table = 'contact_departments';
    public $timestamps = false;
    protected $fillable = ['department_name', 'location_id'];

    public function location()
    {
        return $this->belongsTo(contact_location::class, 'location_id');
    }

    public function employees()
    {
        return $this->hasMany(contact_employees::class, 'id');
    }
}
