<?php
namespace App\Http\Controllers\API\auth;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    /**
     * Récupère le profil de l'utilisateur connecté de manière sécurisée.
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request)
    {
        // Sécurisé : on récupère l'utilisateur via son token d'authentification
        $user = $request->user()->load(['image', 'city', 'departement']);

        return Helpers::success(new UserResource($user));
    }


        /**
     * Récupère la liste de tous les utilisateurs.
     */
    public function getUsers()
    {
        $users = User::with(['image', 'city', 'departement'])->get();

        // On utilise la méthode ::collection() de ton UserResource
        return Helpers::success(UserResource::collection($users));
    }

    /**
     * Change le mot de passe de l'utilisateur connecté.
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword1(Request $request)
    {

        try {
            $user = $request->user();

            // Utilisation du cast 'hashed' de ton modèle User ou Hash::make
            $user->update([
                'password' => $request->password // Automatiquement hashé si Laravel 10/11 avec ton cast
            ]);

            return \App\Helpers\api\Helpers::success([
                'status' => 'success',
                'message' => 'Mot de passe modifié avec succès.'
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur changement mot de passe ID " . ($user->id ?? 'Inconnu') . ": " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur interne est survenue.'
            ], 500);
        }
    }
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            // 1. Validation manuelle directement dans l'action
            $request->validate([
                'old_password' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) use ($user) {
                        // Vérifie si l'ancien mot de passe correspond à celui en BDD
                        if (!Hash::check($value, $user->password)) {
                            $fail("L'ancien mot de passe est incorrect.");
                        }
                    }
                ],
                'password' => 'required|string|min:8|confirmed', // Attend 'password' et 'password_confirmation'
            ]);

            // 2. Mise à jour du mot de passe
            // (Laravel s'occupe du hachage si le cast 'hashed' est présent sur ton modèle User)
            $user->update([
                'password' => $request->password
            ]);

            // 3. Réponse au format de ton Helper personnalisé
            return \App\Helpers\api\Helpers::success([
                'status' => 'success',
                'message' => 'Mot de passe modifié avec succès.'
            ]);

        } catch (ValidationException $e) {
            // Intercepte les erreurs de validation (ex: mot de passe trop court ou mauvaise confirmation)
            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides.',
                'errors' => $e->errors() // Renvoie les détails à ton application Android
            ], 422);

        } catch (\Exception $e) {
            Log::error("Erreur changement mot de passe ID " . ($user->id ?? 'Inconnu') . ": " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur interne est survenue.'
            ], 500);
        }
    }
    /**
     * Met à jour les informations du profil.
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile1(Request $request)
    {
        try {
            $user = $request->user();

            // Mise à jour des données validées uniquement
            $user->update($request->validated());

            // On recharge les relations existantes sur ton modèle User
            $user->load(['image', 'city', 'departement']);

            logger($user);
            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'data' => new UserResource($user) // Retourne la ressource propre pour Android
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur Update Profile ID " . ($user->id ?? 'Inconnu') . ": " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de mettre à jour le profil.'
            ], 500);
        }
    }
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // 1. Validation manuelle directement dans l'action
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'address' => 'nullable|string|max:255',
                'city_id' => 'nullable|exists:cities,id',
                'departement_id' => 'nullable|exists:departements,id',

                // Règle d'unicité propre : ignore l'ID de l'utilisateur connecté
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('users', 'email')->ignore($user->id)
                ],
                'phone' => [
                    'sometimes',
                    'string',
                    Rule::unique('users', 'phone')->ignore($user->id)
                ],
            ]);

            // 2. Mise à jour avec les données validées récupérées ci-dessus
            $user->update($validatedData);

            // 3. Rechargement des relations
            $user->load(['image', 'city', 'departement']);

            logger($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil mis à jour avec succès.',
                'data' => new UserResource($user)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Optionnel : Capturer spécifiquement les erreurs de validation si besoin
            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides.',
                'errors' => $e->errors() // Renvoie les erreurs exactes (ex: email déjà pris)
            ], 422);

        } catch (\Exception $e) {
            Log::error("Erreur Update Profile ID " . ($user->id ?? 'Inconnu') . ": " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de mettre à jour le profil.'
            ], 500);
        }
    }
}
