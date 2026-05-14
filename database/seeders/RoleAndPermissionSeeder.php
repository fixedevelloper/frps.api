<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Nettoyer le cache des permissions (Crucial pour éviter l'erreur array_all)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. DÉFINITION DES MODULES ET ACTIONS
        $modules = [
            'transporteurs' => ['view', 'create', 'edit', 'delete', 'contracts'],
            'vehicules'     => ['view', 'create', 'edit', 'delete', 'maintenance'],
            'products'      => ['view', 'create', 'edit', 'delete', 'pricing'],
            'categories'    => ['view', 'create', 'edit', 'delete'],
            'orders'        => ['view', 'create', 'edit', 'delete', 'validate', 'dispatch', 'invoice'],
            'stocks'        => ['view', 'adjust', 'transfer', 'alerts'],
            'contacts'      => ['view', 'create', 'edit', 'delete'],
            'settings'      => ['view', 'edit'],
            'roles'         => ['manage'],
            'users'         => ['manage'],
            'litiges'       => ['view', 'create', 'edit', 'delete'],
            'retours'       => ['view', 'create', 'edit', 'delete'],
        ];

        // 2. CRÉATION DES PERMISSIONS (Utilisation de firstOrCreate pour éviter les doublons)
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "$module.$action",
                    'guard_name' => 'web'
                ]);
            }
        }

        // 3. CRÉATION OU MISE À JOUR DES RÔLES
        // Super Admin : a tous les droits
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // syncPermissions est plus sûr que givePermissionTo car il écrase et remplace (évite les doublons)
        $adminRole->syncPermissions(Permission::all());

        // Création d'un rôle Agent par défaut (Exemple)
        $agentRole = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);

        // 4. CRÉATION OU MISE À JOUR DE L'UTILISATEUR ADMIN
        // On cherche par le téléphone ou l'email pour éviter les doublons
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@admin.com'], // Condition de recherche
            [
                'name'      => 'Administrateur',
                'phone'     => '69000000',
                'user_type' => 0, // Ajustez selon vos constantes (ex: User::ADMIN_TYPE)
                'password'  => Hash::make('password'),
                'activated' => true,
                'email_verified_at' => now()
            ]
        );
        $adminUser2 = User::updateOrCreate(
            ['email' => 'admin@localhost.com'], // Condition de recherche
            [
                'name'      => 'Super Admin',
                'phone'     => '650000000',
                'user_type' => 0, // Ajustez selon vos constantes (ex: User::ADMIN_TYPE)
                'password'  => Hash::make('123456789'),
                'activated' => true,
                'email_verified_at' => now()
            ]
        );

        // Assigner le rôle (syncRoles évite d'assigner 10 fois le rôle si on lance le seeder 10 fois)
        $adminUser->syncRoles([$adminRole->name]);
        $adminUser2->syncRoles([$adminRole->name]);

        $this->command->info('✅ Seeder terminé : Permissions créées, rôle admin synchronisé et utilisateur admin prêt.');
    }
}
