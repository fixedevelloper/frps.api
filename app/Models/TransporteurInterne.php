<?php

// app/Models/TransporteurInterne.php
namespace App\Models;

use App\Models\Transporteur;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TransporteurInterne extends Model
{
    protected $fillable = [
        'vehicule_id',
        'chauffeur_id',
        'transporteur_id'
    ];

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class);
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

