<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'name' => $this->name,
            'departement_id' => $this->departement_id, // À ne pas oublier !
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
