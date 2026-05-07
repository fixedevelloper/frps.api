<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'intitule' => $this->intitule,
            'price' => $this->price,
            'referenceProduit' => $this->reference,
            'categorie' => $this->category ? $this->category->intitule : null,
            'numeroLot' => $this->lot,
            'quantiteParUnite' => $this->quantite,
            'uniteDeMesure' => $this->unite,
            'poidsDimension' => $this->poids,
            'financement' => $this->financement,
            'utilisateurCible' => $this->utilisateur_cible,
            'dateFabrication' => $this->date_fabrication,
            'datePeremption' => $this->date_peremption,
            'status' => $this->publish ? 'publie' : 'En attente',

            // Logique simplifiée pour l'image
            'image' => ($this->image && $this->image->src)
                ? url(Storage::url($this->image->src))
                : null,
        ];
    }
}
