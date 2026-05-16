<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Charge les villes associées uniquement si elles sont appelées via with('cities')
            'cities' => CityResource::collection($this->whenLoaded('cities')),

            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
