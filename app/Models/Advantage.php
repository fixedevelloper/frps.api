<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advantage extends Model
{
    use HasFactory;

    protected $fillable = [

        'customer_id',

        'type',

        'value',

        'is_percentage',

        'percentage_paid',

        'due_date',

        'active',

    ];

    protected $casts = [

        'value' => 'float',

        'percentage_paid' => 'float',

        'is_percentage' => 'boolean',

        'active' => 'boolean',

        'due_date' => 'date',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function commandes()
    {
        return $this->belongsToMany(
            Commande::class,
            'commande_advantages'
        )->withPivot('amount')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isRemise(): bool
    {
        return $this->type === 'remise';
    }

    public function isBonReduction(): bool
    {
        return $this->type === 'bon_reduction';
    }

    public function isPaiementDiffere(): bool
    {
        return $this->type === 'paiement_differe';
    }
}
