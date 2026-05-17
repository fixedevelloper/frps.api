<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'intitule',
        'category_id',
        'reference',
        'lot',
        'date_fabrication',
        'date_peremption',
        'financement',
        'utilisateur_cible','presentation','description',
        'quantite',
        'unite',
        'poids',
        'price','price_buy','type_stock','stock',
        'image_id',
        'publish'
    ];
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Détermine dynamiquement le lot à charger / consommer en priorité
     * selon la stratégie enregistrée sur le produit (FIFO ou LIFO).
     */
    public function getLotPrioritaireAttribute()
    {
        // 1. On cible uniquement les lots qui ont encore de la marchandise disponible
        $query = $this->stocks()->where('quantite_actuelle', '>', 0);

        // 2. Application de la stratégie algorithmique
        if (strtoupper($this->type_stock) === 'LIFO') {
            // LIFO (Last In, First Out) : On prend le lot arrivé le PLUS RÉCENT en premier
            $query->orderBy('date_reception', 'desc')
                ->orderBy('id', 'desc');
        } else {
            // FIFO (First In, First Out) par défaut ou si FEFO (géré par la date de péremption)
            // On prend le lot arrivé le PLUS ANCIEN en premier
            $query->orderBy('date_reception', 'asc')
                ->orderBy('id', 'asc');
        }

        // 3. Renvoie le premier lot correspondant à la règle, ou null si rupture de stock
        return $query->first();
    }
}
