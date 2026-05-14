<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Departement;
use App\Models\Image;
use App\Models\Litige;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{

    public function departements(Request $request)
    {
        $departements = Departement::all();
        return Helpers::success($departements);
    }
    public function notifications(Request $request)
    {
        $departements = Notification::all();
        return Helpers::success($departements);
    }
    public function cities(Request $request,$departement_id)
    {
        $cities = City::query()->where(['departement_id'=>$departement_id])->get();
        return Helpers::success($cities);
    }
    public function getCustomers(Request $request)
    {
        $litiges = User::with([
            'image',
            'city','departement'
        ])->where(['user_type'=>User::CUSTOMER_TYPE])->get();

        $items = $litiges->map(function ($payment) {
            return [
                'id' => $payment->id,
                'name' => $payment->name,
                'email' => $payment->email,
                'phone' => $payment->phone,
                'date' => $payment->created_at,
                'image' => $payment->image ? $payment->image->src : null,
                'departement' => $payment->departement
                    ? $payment->departement->name
                    : null,
                'city' => $payment->city
                    ? $payment->city->name
                    : null,
            ];
        });

        return Helpers::success($items);
    }
    public function getAgents(Request $request)
    {
        // 1. On ajoute 'roles' dans le eager loading pour éviter le problème N+1
        $agents = User::with([
            'image',
            'city',
            'departement',
            'roles'
        ])
// 1. Filtrer par type d'utilisateur (Agent ou Chauffeur)
            ->whereNotIn('user_type', [User::CUSTOMER_TYPE])

// 2. Exclure ceux qui possèdent le rôle 'admin' chez Spatie
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        $items = $agents->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                // 2. Récupération dynamique du rôle via Spatie
                // On prend le premier rôle assigné, sinon on garde l'ancien système par défaut
                'role' => $user->roles->first() ? $user->roles->first()->name : ($user->user_type == User::DRIVER_TYPE ? 'Chauffeur' : 'Agent'),

                // 3. Optionnel : Liste complète des rôles si un user peut en avoir plusieurs
                'all_roles' => $user->getRoleNames(),

                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->activated,
                'date' => $user->created_at,
                'image' => $user->image ? $user->image->src : null,
                'departement' => $user->departement ? $user->departement->name : null,
                'city' => $user->city ? $user->city->name : null,
            ];
        });

        return Helpers::success($items);
    }
    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'type' => 'required|exists:roles,name',
            'image' => 'nullable|image|max:2048' // max 2 Mo
        ]);


        // Upload image si présente
        $imagePath = null;


        $user= User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'user_type' => User::AGENT_TYPE,
            'password' => Hash::make('123456789'),
        ]);
        $user->assignRole($validated['type']);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $image=Image::create([
                'src'=>$imagePath
            ]);
            $user->image_id=$image->id;
            $user->save();
        }
        return Helpers::success($user,'Agent enregistré');
    }
    public function updateAgent(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'  => 'required|string',
            'phone' => 'required|string',
            // On ignore l'ID actuel pour la validation unique de l'email
            'email' => 'required|email|unique:users,email,' . $id,
            'type'  => 'required|exists:roles,name',
            'image' => 'nullable|image|max:2048'
        ]);

        // 1. Mise à jour des informations de base
        $user->update([
            'name'  => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            // On s'assure que le user_type reste cohérent
            'user_type' => ($validated['type'] === 'chauffeur') ? User::DRIVER_TYPE : User::AGENT_TYPE,
        ]);

        // 2. Mise à jour du rôle Spatie (syncRoles remplace l'ancien rôle par le nouveau)
        $user->syncRoles([$validated['type']]);

        // 3. Gestion de l'image
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('agents', 'public');

            $image = Image::create([
                'src' => $imagePath
            ]);

            $user->image_id = $image->id;
            $user->save();
        }

        return Helpers::success($user->load('roles', 'image'), 'Agent mis à jour avec succès');
    }
    public function updateStatus(Request $request, $id)
    {
        $agent = User::findOrFail($id);

        // On valide que le statut est bien un booléen
        $request->validate([
            'status' => 'required|boolean'
        ]);

        $agent->update([
            'activated' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => $agent->status ? 'Agent activé' : 'Agent désactivé',
            'data' => $agent
        ]);
    }
    // Retourne l'unique Setting
    public function show()
    {
        // On récupère le premier enregistrement
        $setting = Setting::first();

        // Si aucune configuration n'existe, on retourne une erreur claire ou un objet vide
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune configuration trouvée.',
                'data'    => null
            ], 404);
        }

        // On utilise une Resource pour transformer les données (ex: transformer le chemin du logo en URL complète)
        return Helpers::success(new SettingResource($setting), 'Configuration récupérée avec succès');
    }

    // Met à jour l'unique Setting

        public function update(Request $request)
    {
        $setting = Setting::first(); // on suppose qu'il n'y a qu'une seule ligne

        if (!$setting) {
            return response()->json(['message' => 'Paramètres non trouvés'], 404);
        }
        logger($request->all());
        logger(array_map('strlen', $request->all())); // longueur des champs
        // validation optionnelle
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'stock_alert' => 'nullable|numeric',
            'notification_address' => 'nullable|email',
            'notification_phone' => 'nullable|string',
            'dateline_litige' => 'nullable|numeric',
            'percent_payable' => 'nullable|numeric',
        ]);

        // mettre à jour les champs simples
        $fields = [
            'name', 'phone', 'email', 'address', 'stock_alert',
            'notification_address', 'notification_phone',
            'dateline_litige', 'percent_payable'
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting->$field = $request->input($field);
            }
        }

        // gérer le logo
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('logos', 'public'); // stockage dans storage/app/public/logos
            $setting->logo = '/storage/' . $path;
        }

        $setting->save();

        return Helpers::success($setting,'Agent enregistré');
    }
}
