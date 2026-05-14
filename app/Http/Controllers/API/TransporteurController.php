<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransporteurResource;
use App\Models\Chauffeur;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use App\Models\Transporteur;
use App\Models\TransporteurExterne;
use App\Models\TransporteurInterne;
use Illuminate\Support\Facades\DB;

class TransporteurController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:interne,externe',
        ]);

        DB::beginTransaction();
        $transporteur = Transporteur::create([
            'nom' => $request->nom,
            'type' => $request->type,
        ]);

        // Si externe
        if ($request->type === 'externe') {
            $request->validate([
                'contrat' => 'required|string',
                'cout' => 'required|numeric',
                'delai' => 'required|integer',
            ]);
            TransporteurExterne::create([
                'transporteur_id' => $transporteur->id,
                'contrat' => $request->contrat,
                'cout' => $request->cout,
                'delai' => $request->delai,
            ]);
        }

        // Si interne
        if ($request->type === 'interne') {
            $request->validate([
                'vehicule_id' => 'required|exists:vehicules,id',
                'chauffeur_id' => 'required|exists:users,id',
            ]);
            TransporteurInterne::create([
                'transporteur_id' => $transporteur->id,
                'vehicule_id' => $request->vehicule_id,
                'chauffeur_id' => $request->chauffeur_id,
            ]);
        }
        DB::commit();
        return response()->json([
            'message' => 'Transporteur créé avec succès',
            'transporteur' => $transporteur
        ], 201);
    }
    public function storeOrUpdate(Request $request, $id = null)
    {
        $rules = [
            'nom'  => 'required|string|min:3',
            'type' => 'required|in:externe,interne',
        ];

        // Validation conditionnelle
        if ($request->type === 'externe') {
            $rules['contrat'] = 'required|string';
            $rules['cout']    = 'required|numeric|min:0';
        } else {
            $rules['vehicule_id'] = 'required|exists:vehicules,id';
            $rules['chauffeur_id'] = 'required|exists:chauffeurs,id';
        }

        $validated = $request->validate($rules);

        $transporteur = $id ? Transporteur::findOrFail($id) : new Transporteur();
        $transporteur->fill($validated);
        $transporteur->save();

        return response()->json(['message' => 'Succès', 'data' => $transporteur]);
    }
    public function vehicules()
    {
        return Helpers::success(Vehicule::all());
    }
    public function vehiculeShow($id)
    {
        $vehicule = Vehicule::query()->findOrFail($id);

        return Helpers::success($vehicule);
    }
    public function chauffeurs()
    {
        $drivers = User::query()->where(['user_type' => User::DRIVER_TYPE])->get();
        return Helpers::success($drivers);
    }

    public function transporteurs(Request $request)
    {
        $categories = [];
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1); // numéro de la page

        $paginator = Transporteur::with([])->paginate($perPage, ['*'], 'page', $page);

        foreach ($paginator->items() as $cat) {
            $categories[] = [
                'id' => $cat->id,
                'name' => $cat->nom,
                'type' => $cat->type,
            ];
        }

        return response()->json([
            'data' => $categories,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function vehiculestore(Request $request)
    {
        $validated = $request->validate([
            'immatriculation' => 'required|string|max:20|unique:vehicules',
            'modele' => 'required|string|max:255',
            'capacite' => 'required|integer|min:0'
        ]);

        $vehicule = Vehicule::create($validated);

        return response()->json([
            'message' => 'Véhicule ajouté avec succès',
            'vehicule' => $vehicule
        ], 201);
    }
    public function updateVehicule(Request $request, $id)
    {
        // 1. Trouver le véhicule ou renvoyer une erreur 404
        $vehicule = Vehicule::findOrFail($id);

        // 2. Validation des données entrantes
        $validatedData = $request->validate([
            'immatriculation' => 'required|string|max:20|unique:vehicules,immatriculation,' . $id,
            'modele'          => 'required|string|max:255',
            'capacite'        => 'required|numeric|min:1',
        ]);

        // 3. Mise à jour
        $vehicule->update($validatedData);

        // 4. Retourner une réponse JSON
        return response()->json([
            'success' => true,
            'message' => 'Véhicule mis à jour avec succès',
            'data'    => $vehicule
        ], 200);
    }
    public function index()
    {
        $transporteurs = Transporteur::with(['externe', 'interne.vehicule', 'interne.chauffeur'])
            ->paginate(10);

        return TransporteurResource::collection($transporteurs);
    }

   public function show($id)
    {
        $transporteur = Transporteur::with(['externe', 'interne.vehicule', 'interne.chauffeur'])
            ->findOrFail($id);

        return Helpers::success(new TransporteurResource($transporteur));
    }
    public function show2($id)
    {
        $transporteur = Transporteur::with([
            'transporteurExterne',
            'transporteurInterne.vehicule',
            'transporteurInterne.chauffeur'
        ])->findOrFail($id);

        return Helpers::success($transporteur);
    }

    public function destroyVehicule(Vehicule $litige)
    {
        $litige->delete();

        return Helpers::success([
            'message' => 'Litige supprimé'
        ]);
    }
    public function destroyDriver(Transporteur $litige)
    {
        $litige->delete();

        return Helpers::success([
            'message' => 'Litige supprimé'
        ]);
    }
    public function deleteTransporteur($id)
    {
        $transporteur = Transporteur::find($id);

        if (!$transporteur) {
            return response()->json([
                'success' => false,
                'message' => 'Transporteur introuvable.'
            ], 404);
        }

        // Optionnel : Vérifier si le transporteur est lié à des commandes
        // if ($transporteur->orders()->exists()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Impossible de supprimer un transporteur lié à des commandes.'
        //     ], 422);
        // }

        $transporteur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transporteur supprimé avec succès.'
        ], 200);
    }
}

