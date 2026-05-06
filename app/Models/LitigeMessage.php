<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitigeMessage extends Model
{
    protected $fillable = [
        'litige_id',
        'type',
        'user_id',
        'message',
        'attachments'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function litige(): BelongsTo
    {
        return $this->belongsTo(Litige::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
