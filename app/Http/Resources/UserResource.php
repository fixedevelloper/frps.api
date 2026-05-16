<?php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'user_type' => $this->user_type,

            // Permet d'avoir le nom du type en texte plutôt qu'un chiffre
            'user_type_label' => $this->getUserTypeLabel(),

            'activated' => (bool) $this->activated,
            'discount_rate' => (float) $this->discount_rate,
            'pending_balance' => (float) $this->pending_balance,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toIso8601String() : null,

            // Chargement conditionnel des relations (évite les requêtes inutiles)
            'image' => new ImageResource($this->whenLoaded('image')),
            'city' => new CityResource($this->whenLoaded('city')),
            'departement' => new DepartementResource($this->whenLoaded('departement')),

            // Si tu as besoin de lister les rôles (via le trait HasRoles)
/*            'roles' => $this->when($this->relationLoaded('roles'), function () {
                return $this->roles->pluck('name');
            }),*/

            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }

    /**
     * Convertit le code numérique du type d'utilisateur en texte lisible.
     */
    private function getUserTypeLabel(): string
    {
        return match ($this->user_type) {
        \App\Models\User::ADMIN_TYPE => 'Admin',
            \App\Models\User::AGENT_TYPE => 'Agent',
            \App\Models\User::DRIVER_TYPE => 'Driver',
            \App\Models\User::CUSTOMER_TYPE => 'Customer',
            default => 'Unknown',
        };
    }
}
