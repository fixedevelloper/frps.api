<?php
// app/Models/Vehicule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicule extends Model
{
    protected $fillable = [
        'immatriculation',
        'modele',
        'capacite', 'marque'
    ];

    public function transporteurInterne(): HasOne
    {
        return $this->hasOne(TransporteurInterne::class);
    }
}

