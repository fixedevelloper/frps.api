<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper; // Assure-toi que le Helper est importé

class EnterStock extends Model
{
    protected $fillable = [
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
     * Relation avec l'Utilisateur (Auteur)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Logique automatique lors de la création
     */
    protected static function booted()
    {
        static::creating(function ($enterStock) {
            // 1. Automatisation de l'auteur
            if (Auth::check()) {
                $enterStock->created_by = Auth::id();
            }

            // 2. Génération automatique d'une référence unique si absente
            if (empty($enterStock->reference)) {
                $enterStock->reference = 'STK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            }

            // 3. Gestion du stock avec verrouillage (Transaction sécurisée)
            DB::transaction(function () use ($enterStock) {
                // On récupère le produit avec lock pour éviter les accès simultanés
                $product = Product::where('id', $enterStock->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \Exception("Le produit spécifié n'existe pas.");
                }

                // On enregistre la photo du stock avant modification
                $enterStock->previous_quantity = $product->quantite;

                // Mise à jour de la quantité du produit
                $newQuantity = $product->quantite + $enterStock->quantity;

                // Sécurité contre le stock négatif
                if ($newQuantity < 0) {
                    throw new \Exception("Action refusée : Stock insuffisant pour le produit {$product->intitule}.");
                }

                $product->quantite = $newQuantity;
                $product->save();
            });
        });
    }
}
