<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'commande_id',
        'montant',
        'methode',
        'status',
        'etat',
        'reference',
        'date_paiement',
        'provider_response'
    ];

    protected $casts = [
        'provider_response' => 'array',
        'date_paiement' => 'datetime',
        'montant' => 'decimal:2'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStringMethodeAttribute()
    {
        $methodes = [
            Helper::METHODMTN => [
                'class' => 'badge badge-success',
                'value' => 'MTN Money'
            ],
            Helper::METHODOM => [
                'class' => 'badge badge-warning',
                'value' => 'Orange Money'
            ],
            Helper::METHODCHECK => [
                'class' => 'badge badge-danger',
                'value' => 'Carte bancaire'
            ],
            Helper::METHODCASH => [
                'class' => 'badge badge-success',
                'value' => 'Cash'
            ]
        ];

        return (object) ($methodes[$this->methode] ?? [
                'class' => 'badge badge-secondary',
                'value' => 'Inconnu'
            ]);
    }

}
