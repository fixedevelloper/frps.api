<?php


namespace App\Http\Controllers\API\auth;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    // Login client (CUSTOMER)
    public function login(Request $request)
    {
        $credentials = $request->only('code', 'password');

        if (!Auth::attempt($credentials)) {
            return Helpers::unauthorized(['message' => 'Identifiants invalides'], 401);
        }

        $user = auth()->user();

        if ($user->user_type != User::CUSTOMER_TYPE) {
            return Helpers::unauthorized(['message' => 'Accès réservé aux clients'], 403);
        }

        // Création du token Sanctum
        $token = $user->createToken('mobile-app')->plainTextToken;

        return Helpers::success([
            'message' => 'Connexion réussie',
            'token' => $token,
            'phone' => $user->phone,
            'username' => $user->name
        ]);
    }

    // Login admin/personnel
    public function loginAdmin(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        if (!Auth::attempt($credentials)) {
            return Helpers::unauthorized(['message' => 'Identifiants invalides'], 401);
        }

        $user = auth()->user();

        if ($user->user_type == User::CUSTOMER_TYPE) {
            return Helpers::unauthorized(['message' => 'Accès réservé au personnel'], 403);
        }

        // Création du token Sanctum
        $token = $user->createToken('mobile-app')->plainTextToken;
        $permissions = $user->getAllPermissions()->pluck('name');
        return Helpers::success([
            'message' => 'Connexion réussie',
            'token' => $token,
            'phone' => $user->phone,
            'username' => $user->name,
            'permissions' => $permissions
        ]);
    }

    // Logout (supprime tous les tokens de l'utilisateur pour mobile)
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete(); // supprime tous les tokens API
        }

        return Helpers::success(['message' => 'Déconnexion réussie']);
    }
}
