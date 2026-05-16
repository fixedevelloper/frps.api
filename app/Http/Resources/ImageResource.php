<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ImageResource extends JsonResource
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
            'src' => $this->src,

            // Génère une URL absolue (pratique pour le front-end)
            'url' => $this->getFullUrl($this->src),

            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }

    /**
     * Helper pour retourner l'URL complète du fichier.
     */
    private function getFullUrl(?string $src): ?string
    {
        if (!$src) {
            return null;
        }

        // Si l'image est déjà une URL absolue (ex: hébergée sur S3 ou un CDN externer)
        if (Str::startsWith($src, ['http://', 'https://'])) {
            return $src;
        }

        // Si c'est un chemin local (ex: "uploads/users/avatar.jpg"), on utilise le helper asset()
        return asset($src);
    }
}
