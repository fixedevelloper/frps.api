<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable=[
      'name','phone','email','address','logo','stock_alert','notification_address','notification_phone','dateline_litige','percent_payable'
    ];
}
