<?php


namespace App\Http\Controllers\API;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec pagination et recherche
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::with(['image', 'city', 'departement','roles']);

        if ($request->has('type')) {
            $query->where('user_type', $request->type);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $users = $query->latest()->paginate(10);
        return Helpers::success($users, "Liste récupérée");
    }

    /**
     * Créer ou Mettre à jour (Logic unique pour l'ajout et l'édition)
     */
    public function storeOrUpdate(Request $request, $id = null)
    {
        $isUpdate = $id !== null;
        $user = $isUpdate ? User::findOrFail($id) : new User();

        // 1. Validation
        $request->validate([
            'name'           => 'required|string|max:255',
            'user_type'      => 'nullable|integer|in:0,1,2,3',
            'phone'          => 'required|string|unique:users,phone,' . ($id ?? 'NULL'),
            'email'          => 'nullable|email|unique:users,email,' . ($id ?? 'NULL'),
            'role'  => 'required|exists:roles,name',
            'address'        => 'nullable|string',
            'city_id'        => 'nullable|exists:cities,id',
            'departement_id' => 'nullable|exists:departements,id',
            'discount_rate'  => 'nullable|numeric|min:0|max:100',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password'       => !$isUpdate ? 'nullable|min:6' : 'nullable',
        ]);

        try {
            DB::beginTransaction();

            // 2. Gestion de l'image physique
            $imageId = $user->image_id; // Garde l'ancienne par défaut

            if ($request->hasFile('image')) {
                // Supprimer l'ancienne image du disque si elle existe
                if ($user->image) {
                    Storage::disk('public')->delete($user->image->path);
                    $user->image->delete();
                }

                // Stocker la nouvelle
                $path = $request->file('image')->store('users', 'public');
                $newImage = Image::create([
                    'url' => asset('storage/' . $path),
                    'path' => $path
                ]);
                $imageId = $newImage->id;
            }

            // 3. Assignation des données
            $user->fill($request->only([
                'name', 'user_type', 'phone', 'email', 'address',
                'city_id', 'departement_id', 'discount_rate'
            ]));

            $user->image_id = $imageId;

            // Mot de passe : uniquement si fourni, sinon défaut à la création
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            } elseif (!$isUpdate) {
                $user->password = Hash::make('12345678');
            }

            if (!$isUpdate) {
                $user->activated = true;
                $user->pending_balance = 0.0;
            }
            $user->syncRoles([$request->role]);
            $user->save();

            DB::commit();

            return Helpers::success($user, $isUpdate ? "Agent mis à jour" : "Agent créé");

        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::error("Erreur serveur: " . $e->getMessage(), 500);
        }
    }


    public function show($id)
    {
        $user = User::with([
            'image',
            'city',
            'departement',
            'roles',
        ])->find($id);

        if (!$user) {
            return Helpers::error("Utilisateur introuvable", 404);
        }

        // récupérer le premier rôle
        $user->role = $user->roles->first()?->name;

    // optionnel : masquer la collection complète des rôles
    unset($user->roles);

    return Helpers::success($user);
}

    /**
     * Suppression
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->image) {
            Storage::disk('public')->delete($user->image->path);
            $user->image->delete();
        }

        $user->delete();
        return Helpers::success(null, "Utilisateur supprimé définitivement");
    }
    // Réinitialiser le mot de passe
    public function resetToDefault(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($request->user_id);

        // Définition du mot de passe par défaut
        $defaultPassword = 'Password123';

        $user->update([
            'password' => Hash::make($defaultPassword),
            // Optionnel : on peut forcer l'utilisateur à changer de mot de passe au prochain login
            'must_change_password' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Le mot de passe de {$user->name} a été réinitialisé à : {$defaultPassword}"
        ], 200);
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
    public function updateInfo(Request $request)
    {
        $user = Auth::user(); // ou $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Non authentifié.'], 401);
        }

        // Ici, vous pouvez utiliser $user->id en toute sécurité
        $user->update($request->only(['name', 'email', 'phone']));

        return response()->json(['status' => 'success', 'message' => 'Profil mis à jour']);
    }
}
