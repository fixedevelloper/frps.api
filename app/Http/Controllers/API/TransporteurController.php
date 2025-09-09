<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
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

    public function vehicules()
    {
        return Helpers::success(Vehicule::all());
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
    public function show($id)
    {
        $transporteur = Transporteur::with(['transporteurExterne', 'transporteurInterne'])->findOrFail($id);

        return Helpers::success($transporteur);
    }
}

