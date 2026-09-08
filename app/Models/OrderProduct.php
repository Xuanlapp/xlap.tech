<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    protected $table = 'order_product';

    protected $fillable = [
        'source_id',
        'fulfillment_type',
        'order_product_id',
        'product_name',
    ];
}
