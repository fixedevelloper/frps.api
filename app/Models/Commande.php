<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'customer_id',
        'status',
        'date_validation',
        'adresse_livraison',
        'qr_code',
        'suivi_url',
        'total',
        'timer_auto',
        'validatedStatus',
        'validatedBy',
        'rest_to_pay',
        'reference',
        'facture_pdf',
        'bordereau_pdf',
        'proforma_pdf'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class,'customer_id','id');
    }
    public function products()
    {
        return $this->hasMany(ProductCommande::class, 'commande_id');
    }

    public function paiement()
    {
        return $this->hasMany(Paiement::class);
    }

    public function transporteur()
    {
        return $this->belongsTo(Transporteur::class);
    }

    public function litiges()
    {
        return $this->hasMany(Litige::class, 'commande_id');
    }

    public function getStringStatusAttribute() {
        $statusMap = [
            Helper::STATUSSUCCESS     => ['class' => 'badge badge--success', 'value' => 'Succès'],
            Helper::STATUSPENDING     => ['class' => 'badge badge--warning', 'value' => 'En attente'],
            Helper::STATUSREJECTED     => ['class' => 'badge badge--warning', 'value' => 'Rejeté'],
            Helper::STATUSCONFIRM     => ['class' => 'badge badge--warning', 'value' => 'Confirmé'],
            Helper::STATUSDELIVERYD   => ['class' => 'badge badge--danger',  'value' => 'Livré'],
            Helper::STATUSWAITING     => ['class' => 'badge badge--danger',  'value' => 'En cours de livraison'],
            Helper::STATUSFAILD       => ['class' => 'badge badge--danger',  'value' => 'En investigation'],
            Helper::STATUSPROCESSING  => ['class' => 'badge badge--warning', 'value' => 'En préparation'],
        ];

        return (object) ($statusMap[$this->status] ?? ['class' => '', 'value' => 'Inconnu']);
    }
    public function getStringValidatedStatusAttribute() {
        $statusMap = [
            Helper::STATUSAUTOCONFIRM => [
                'class' => 'badge badge--success',
                'value' => 'Confirmation auto'
            ],
            Helper::STATUSCONFIRM => [
                'class' => 'badge badge--warning',
                'value' => 'Confirmé'
            ],
            Helper::STATUSPENDING => [
                'class' => 'badge badge--danger',
                'value' => 'En attente'
            ],
            Helper::STATUSREJECTED => [
                'class' => 'badge badge--danger',
                'value' => 'Rejeté'
            ],
        ];

        return (object) ($statusMap[$this->validatedStatus] ?? [
                'class' => 'badge badge--secondary',
                'value' => 'Inconnu'
            ]);
    }

}
