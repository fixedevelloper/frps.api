<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Litige extends Model
{
    protected $fillable = [
        'commande_id',
        'user_id',
        'type',
        'status',
        'description',
        'submitted_at',
        'resolution_deadline',
        'resolved_at',
        'admin_comment',
        'refund_amount'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'resolution_deadline' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function messages()
    {
        return $this->hasMany(LitigeMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /*
|--------------------------------------------------------------------------
| ACCESSORS
|--------------------------------------------------------------------------
*/

    /**
     * Accesseur pour obtenir le badge et le libellé formaté
     * Usage côté API : $litige->string_status
     */
    public function getStringStatusAttribute()
    {
        $statuses = [
            'Ouvert' => [
                'class' => 'bg-danger-subtle text-danger', // Style "Soft" moderne
                'value' => 'Ouvert'
            ],
            'En cours' => [
                'class' => 'bg-warning-subtle text-warning',
                'value' => 'En traitement'
            ],
            'Résolu' => [
                'class' => 'bg-success-subtle text-success',
                'value' => 'Résolu'
            ],
            'Fermé' => [
                'class' => 'bg-secondary-subtle text-secondary',
                'value' => 'Clôturé'
            ],
        ];

        // On retourne un objet pour un accès facile en JSON (ex: item.string_status.class)
        return (object) ($statuses[$this->status] ?? [
                'class' => 'bg-light text-dark',
                'value' => $this->status ?? 'Inconnu'
            ]);
    }

    /**
     * N'oubliez pas d'ajouter l'attribut à la sérialisation JSON
     */
    protected $appends = ['string_status'];

    public function getStringTypeAttribute()
    {
        $types = [
            'delivery' => 'Problème de livraison',
            'product' => 'Produit défectueux',
            'refund' => 'Demande de remboursement',
            'other' => 'Autre'
        ];

        return $types[$this->type] ?? 'Autre';
    }

}
