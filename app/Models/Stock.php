<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'product_id', 'num_lot', 'date_peremption',
        'date_reception', 'quantite_initiale',
        'quantite_actuelle', 'prix_achat', 'emplacement'
    ];

    protected $casts = [
        'date_peremption' => 'date',
        'date_reception' => 'date',
        'quantite_initiale' => 'integer',
        'quantite_actuelle' => 'integer',
    ];

    // Récupérer uniquement les lots où il reste du stock
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('quantite_actuelle', '>', 0);
    }

    // Tri FIFO : Premier entré (par date de réception ou id le plus ancien), premier sorti
    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderBy('date_reception', 'asc')->orderBy('id', 'asc');
    }

    // Tri LIFO : Dernier entré, premier sorti
    public function scopeLifo(Builder $query): Builder
    {
        return $query->orderBy('date_reception', 'desc')->orderBy('id', 'desc');
    }

    // FEFO (First Expired, First Out) : Variante indispensable si péremption proche
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('date_peremption', 'asc');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
