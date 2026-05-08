<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EnterStock extends Model
{
    public $fillable = [
        'quantity',
        'product_id',
        'previous_quantity',
        'created_by',
        'status',
        'reference'
    ];

    /**
     * Relation avec le Produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Logique automatique lors de la création
     */
    protected static function booted()
    {
        static::creating(function ($enterStock) {
            // On récupère le produit avec un verrou (lock) pour éviter les erreurs de calcul simultanés
            $product = Product::where('id', $enterStock->product_id)->lockForUpdate()->first();

            if ($product) {
                // On enregistre l'état actuel avant modification
                $enterStock->previous_quantity = $product->quantite;

                // IMPORTANT : Si c'est une vente, la quantité passée doit être négative
                // On met à jour le stock du produit
                $product->quantite += $enterStock->quantity;

                if ($product->quantite < 0) {
                    throw new \Exception("Action impossible : le stock deviendrait négatif.");
                }

                $product->save();
            }
        });
    }
}
