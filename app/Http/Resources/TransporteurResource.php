<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransporteurResource extends JsonResource
{
    public function toArray($request)
    {
        // On fusionne les données de base avec les données spécifiques
        $data = [
            'id' => $this->id,
            'nom' => $this->nom,
            'type' => $this->type,
            'created_at' => $this->created_at->format('d/m/Y'),
        ];

        if ($this->type === 'externe' && $this->externe) {
            $data = array_merge($data, [
                'contrat' => $this->externe->contrat,
                'cout' => $this->externe->cout,
                'delai' => $this->externe->delai,
            ]);
        }

        if ($this->type === 'interne' && $this->interne) {
            $data = array_merge($data, [
                'vehicule_id' => $this->interne->vehicule_id,
                'chauffeur_id' => $this->interne->chauffeur_id,
                // On peut inclure les détails si les relations sont chargées
                'vehicule' => $this->interne->vehicule ? [
                    'immatriculation' => $this->interne->vehicule->immatriculation,
                    'modele' => $this->interne->vehicule->modele,
                ] : null,
                'chauffeur' => $this->interne->chauffeur ? [
                    'name' => $this->interne->chauffeur->name,
                ] : null,
            ]);
        }

        return $data;
    }
}
