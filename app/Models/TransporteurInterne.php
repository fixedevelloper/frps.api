<?php

// app/Models/TransporteurInterne.php
namespace App\Models;

use App\Models\Transporteur;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporteurInterne extends Transporteur
{
    protected $fillable = ['vehicule_id', 'chauffeur_id','transporteur_id'];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class);
    }
}

