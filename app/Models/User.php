<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
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
    // Ajoutez cette méthode pour récupérer toutes les notifications de l'utilisateur
    public function notifications()
    {
        // C'est une relation polymorphique native de Laravel
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
            ->latest();
    }
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Validation stricte des données entrantes
        $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => [
                'required',
                'string',
                'min:6', // Aligné avec votre validation Angular (minLength: 6)
                'confirmed' // S'assure que 'newPassword_confirmation' est présent et identique
            ],
        ], [
            'currentPassword.required' => 'Le mot de passe actuel est obligatoire.',
            'newPassword.required' => 'Le nouveau mot de passe est obligatoire.',
            'newPassword.min' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
            'newPassword.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        // 2. Vérification que le mot de passe actuel est correct
        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le mot de passe actuel est incorrect.'
            ], 422); // Code 422: Unprocessable Entity
        }

        // 3. Mise à jour sécurisée en base de données
        $user->update([
            'password' => Hash::make($request->newPassword)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe mis à jour avec succès.'
        ]);
    }
}
