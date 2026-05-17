<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numeroLot' => $this->num_lot,
            'quantiteRestante' => $this->quantite_actuelle,
            'quantiteInitiale' => $this->quantite_initiale,
            'datePeremption' => $this->date_peremption,
            'dateReception' => $this->date_reception,
            'emplacement' => $this->emplacement,
            // Des indicateurs légers et utiles calculés à la demande
            'nombreMouvements' => $this->whenCounted('movements'),
            'mouvements' => StockMovementResource::collection($this->whenLoaded('movements')),
        ];
    }
}
