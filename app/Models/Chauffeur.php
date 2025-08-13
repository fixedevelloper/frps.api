<?php

// app/Models/Chauffeur.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chauffeur extends Model
{
    protected $fillable = ['nom', 'contact'];

    public function transporteurInterne()
    {
        return $this->hasOne(TransporteurInterne::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }
}
