<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterStock extends Model
{
    public $fillable=[
        'quantity',
        'product_id',
        'previous_quantity',
        'created_by'
    ];
}
