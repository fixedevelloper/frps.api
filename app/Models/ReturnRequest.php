<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $table = "returnRequests";
    protected $fillable=[
        'commande_id','product_order_id','reason','date_demande','return_label','date_traitement'
    ];
}
