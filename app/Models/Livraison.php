<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    protected $fillable = [
        'bordereau_id', 'satisfaction', 'conformite', 'statut'
    ];

    public function bordereau()
    {
        return $this->belongsTo(Bordereau::class);
    }
}
