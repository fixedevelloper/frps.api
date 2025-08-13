<?php

// app/Models/TransporteurExterne.php
namespace App\Models;

use App\Models\Transporteur;

class TransporteurExterne extends Transporteur
{
    protected $fillable = ['contrat', 'cout', 'delai','transporteur_id'];
}
