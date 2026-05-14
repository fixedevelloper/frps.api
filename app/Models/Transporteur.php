<?php

// app/Models/Transporteur.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporteur extends Model
{
    protected $fillable = [
        'nom',
        'type','vehicule','chauffeur'
    ];

    // Relation vers les détails externes
    public function externe()
    {
        return $this->hasOne(TransporteurExterne::class);
    }

    // Relation vers les détails internes
    public function interne()
    {
        return $this->hasOne(TransporteurInterne::class);
    }

    public function transporteurExterne()
    {
        return $this->hasOne(TransporteurExterne::class);
    }

    public function transporteurInterne()
    {
        return $this->hasOne(TransporteurInterne::class)
            ->with(['vehicule','chauffeur']);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }
}
