<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCommande extends Model
{
    protected $table = "product_commande";
    protected $fillable = [
        'commande_id', 'product_id', 'quantite', 'amount'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
