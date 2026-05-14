<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'phone'                => $this->phone,
            'email'                => $this->email,
            'address'              => $this->address,
            // Génère l'URL complète pour le logo
            'logo_url'             => $this->logo ? asset('storage/' . $this->logo) : asset('assets/images/default-logo.png'),
            'stock_alert'          => (int) $this->stock_alert,
            'notification_address' => $this->notification_address,
            'notification_phone'   => $this->notification_phone,
            'dateline_litige'      => (int) $this->dateline_litige,
            'percent_payable'      => (float) $this->percent_payable,
            'updated_at'           => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
