<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'notifiable_id',
        'notifiable_type',
        'data',
        'read_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'data'    => 'array', // Transforme automatiquement le JSON en tableau
    ];

    /**
     * Obtenir le modèle notifiable propriétaire.
     */
    public function notifiable()
    {
        return $this->morphTo();
    }
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }
}
