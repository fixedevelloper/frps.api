<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bordereau extends Model
{
    protected $fillable = [
        'commande_id', 'transporteur', 'date_estimee_livraison', 'numero_suivi'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function reception()
    {
        return $this->hasOne(Livraison::class);
    }
}
