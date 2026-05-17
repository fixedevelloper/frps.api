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
        $lotPrioritaire = $this->lot_prioritaire;
        return [
            'id' => $this->id,
            'intitule' => $this->intitule,
            'price' => $this->price,
            'price_buy'=> $this->price_buy,
            'type_stock'=>$this->type_stock,
            'referenceProduit' => $this->reference,
            'categorie' => $this->category ? $this->category->intitule : null,
            'categorie_id' => $this->category_id,
            'numeroLot' => $this->lot,
            'quantiteParUnite' => $this->quantite,
            'uniteDeMesure' => $this->unite,
            'poidsDimension' => $this->poids,
            'financement' => $this->financement,
            'utilisateurCible' => $this->utilisateur_cible,
            'dateFabrication' => $this->date_fabrication,
            'datePeremption' => $this->date_peremption,
            'presentation' => $this->presentation,
            'description' => $this->description,
            'lot_actuel' => $lotPrioritaire ? [
                'id' => $lotPrioritaire->id,
                'numeroLot' => $lotPrioritaire->num_lot,
                'quantiteDisponible' => $lotPrioritaire->quantite_actuelle,
                'datePeremption' => $lotPrioritaire->date_peremption,
                'emplacement' => $lotPrioritaire->emplacement,
            ] : null,

            // Stock global cumulé (Somme de tous les lots disponibles)
            'stock_total' => (int) $this->stocks()->sum('quantite_actuelle'),
            'status' => $this->publish ? 'publie' : 'En attente',

            // Logique simplifiée pour l'image
            'image' => ($this->image && $this->image->src)
                ? url(Storage::url($this->image->src))
                : null,
        ];
    }
}
