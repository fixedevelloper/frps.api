<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    const ORDERTYPE='ORDER_TYPE';
    const PRODUCTTYPE='PRODUCT_TYPE';
    protected $fillable=[
        'from_id',
        'to_id',
        'description','type'
    ];
}
