<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    const ADMIN_TYPE = 0;
    const AGENT_TYPE = 3;
    const DRIVER_TYPE = 1;
    const CUSTOMER_TYPE = 2;
    use SoftDeletes;
    protected $dates = ['email_verified_at'];

    protected $fillable = [
        'name', 'user_type', 'address', 'phone', 'email',
        'password', 'activated', 'discount_rate', 'pending_balance',
        'image_id', 'city_id', 'departement_id','code','type'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activated' => 'boolean',
        'discount_rate' => 'float',
        'pending_balance' => 'float',
        'password' => 'hashed',
    ];

    public function image() { return $this->belongsTo(Image::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function departement() { return $this->belongsTo(Departement::class); }
    public function commandes() { return $this->hasMany(Commande::class); }
    public function advantages() { return $this->hasMany(Advantage::class, 'customer_id'); }

    public function routeNotificationForSms() { return $this->phone; }

    public function sendPasswordResetNotification($token)
    {
        $url = config('app.frontend.url') . '/auth/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        Mail::to($this->email)->send(new ResetPasswordMail($url));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }
}
