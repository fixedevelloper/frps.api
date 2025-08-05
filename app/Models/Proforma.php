<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    protected $fillable = [
        'etat_besoin_id', 'statut', 'pdf_url'
    ];

    public function etatBesoin()
    {
        return $this->belongsTo(EtatBesoin::class);
    }

    public function commande()
    {
        return $this->hasOne(Commande::class);
    }
}
