<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterStockResource extends JsonResource
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
            'quantite_ajoutee' => $this->quantity,
            'ancien_stock' => $this->previous_quantity,
            'nouveau_stock' => $this->previous_quantity + $this->quantity,
            'statut' => $this->status,

            // Informations sur le produit
            'produit' => [
                'id' => $this->product->id,
                'intitule' => $this->product->intitule,
                'reference' => $this->product->referenceProduit,
            ],

            // Informations sur l'auteur
            'auteur' => [
                'id' => $this->created_by,
                'nom' => $this->creator ? $this->creator->name : 'Système',
            ],

            'date_entree' => $this->created_at->format('d/MM/yyyy H:i'),
        ];
    }
}
