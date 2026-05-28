<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contact_employees extends Model
{
    use HasFactory;

    protected $table = 'contact_employees';
    public $timestamps = false;
    protected $fillable = ['name', 'email', 'phone', 'position', 'department_id', 'profile_image'];

    public function department()
    {
        return $this->belongsTo(contact_department::class, 'department_id');
    }
}
