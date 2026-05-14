<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    // Récupérer tous les rôles avec leurs permissions
    public function index()
    {
        return response()->json([
            'roles' => Role::with('permissions')->get(),
            'all_permissions' => Permission::all()
        ]);
    }

    // Mettre à jour les permissions d'un rôle
    public function updatePermissions(Request $request, $roleId)
    {
        // 1. Validation : on s'assure que 'permissions' est un tableau
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            $role = Role::findOrFail($roleId);

            // 2. Synchronisation
            $role->syncPermissions($request->permissions);

            // 3. Purger le cache des permissions (Optionnel mais recommandé)
            // Spatie le fait souvent seul, mais cela force la mise à jour immédiate
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return response()->json([
                'status' => 'success',
                'message' => 'Permissions synchronisées avec succès',
                'count' => count($request->permissions)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    // Créer un nouveau rôle
    public function storeRole(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles,name']);
        $role = Role::create(['name' => $request->name]);
        return response()->json($role);
    }
}
