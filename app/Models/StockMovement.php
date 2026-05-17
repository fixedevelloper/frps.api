<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'stock_id', 'type', 'quantite',
        'movable_type', 'movable_id', 'user_id', 'motif'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Permet de lier le mouvement à un autre modèle (Order, ReturnRequest, etc.)
     */
    public function movable(): MorphTo
    {
        return $this->morphTo();
    }
}
