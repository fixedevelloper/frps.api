<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\ResetPasswordMail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject,MustVerifyEmail
{
    use HasFactory, Notifiable;


    const ADMIN_TYPE = 0;
    const AGENT_TYPE = 3;
    const DRIVER_TYPE = 1;
    const CUSTOMER_TYPE = 2;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    protected $dates = ['email_verified_at'];
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'departement_id',
        'city_id',
        'phone',
        'email',
        'password','user_type'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
    public function routeNotificationForSms()
    {
        return $this->phone; // colonne phone dans la DB
    }
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
