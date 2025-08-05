<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model

{
    protected $fillable = [
        'commande_id', 'montant', 'methode', 'status', 'etat','reference','date_paiement'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
    public function getStringMethodeAttribute() {
        $statusMap = [
            Helper::METHODMTN => [
                'class' => 'badge badge--success',
                'value' => 'MTN'
            ],
            Helper::METHODOM => [
                'class' => 'badge badge--warning',
                'value' => 'Orange money'
            ],
            Helper::METHODCHECK => [
                'class' => 'badge badge--danger',
                'value' => 'Carte bancaire'
            ],

        ];

        return (object) ($statusMap[$this->methode] ?? [
                'class' => 'badge badge--secondary',
                'value' => 'Inconnu'
            ]);
    }
}
