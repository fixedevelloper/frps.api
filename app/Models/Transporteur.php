<?php

// app/Models/Transporteur.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporteur extends Model
{
    protected $fillable = [
        'nom',
        'type', // 'interne' ou 'externe'
    ];

    public function transporteurExterne()
    {
        return $this->hasOne(TransporteurExterne::class);
    }

    public function transporteurInterne()
    {
        return $this->hasOne(TransporteurInterne::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }
}
