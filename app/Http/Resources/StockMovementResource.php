<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stockId' => $this->stock_id,
            'type' => $this->type, // ex: 'ENTREE', 'SORTIE', 'AJUSTEMENT', 'RETOUR'
            'quantite' => $this->quantite,
            'motif' => $this->motif, // ex: 'Vente commande #120', 'Inventaire annuel'
            'creePar' => $this->user ? $this->user->name : 'Système',
            'dateMouvement' => $this->created_at ? $this->created_at->toIso8601String() : null,

            // Inclusion conditionnelle du lot parent si nécessaire
            'lot' => new StockResource($this->whenLoaded('stock')),
        ];
    }
}
