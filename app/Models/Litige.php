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

    public function getStringStatusAttribute()
    {
        $statuses = [
            'pending' => [
                'class' => 'badge bg-warning',
                'value' => 'En attente'
            ],
            'processing' => [
                'class' => 'badge bg-primary',
                'value' => 'En traitement'
            ],
            'resolved' => [
                'class' => 'badge bg-success',
                'value' => 'Résolu'
            ],
            'rejected' => [
                'class' => 'badge bg-danger',
                'value' => 'Rejeté'
            ],
        ];

        return (object) ($statuses[$this->status] ?? [
                'class' => 'badge bg-secondary',
                'value' => 'Inconnu'
            ]);
    }

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
