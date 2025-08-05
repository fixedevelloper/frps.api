<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Litige extends Model
{
    protected $fillable = [
        'commande_id',
        'status',
        'type',
        'description',
        'submitted_at',
        'resolution_deadline',
        'photos'
    ];
}
