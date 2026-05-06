<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = [
        'commande_id',
        'product_order_id',
        'reason',
        'status',
        'return_label',
        'date_demande',
        'date_traitement'
    ];

    protected $casts = [
        'date_demande' => 'datetime',
        'date_traitement' => 'datetime',
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

    public function productOrder(): BelongsTo
    {
        return $this->belongsTo(ProductCommande::class, 'product_order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute()
    {
        $map = [
            'pending' => [
                'class' => 'badge bg-warning',
                'value' => 'En attente'
            ],
            'approved' => [
                'class' => 'badge bg-success',
                'value' => 'Approuvé'
            ],
            'rejected' => [
                'class' => 'badge bg-danger',
                'value' => 'Refusé'
            ],
            'processed' => [
                'class' => 'badge bg-primary',
                'value' => 'Traité'
            ],
        ];

        return (object) ($map[$this->status] ?? [
                'class' => 'badge bg-secondary',
                'value' => 'Inconnu'
            ]);
    }
}
