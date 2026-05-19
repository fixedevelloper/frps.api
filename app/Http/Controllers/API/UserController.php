<?php


namespace App\Http\Controllers\API;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec pagination et recherche
     */
    public function index(Request $request)
    {
        $query = User::with(['image', 'city', 'departement']);

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
}
