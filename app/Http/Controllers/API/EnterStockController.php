<?php


namespace App\Http\Controllers\API;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnterStockResource;
use App\Models\EnterStock;
use App\Models\Product;
use App\Helpers\Helper; // Assurez-vous d'avoir votre Helper pour les status
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnterStockController extends Controller
{
    /**
     * Enregistrer un mouvement de stock (Entrée ou Sortie)
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric', // Peut être négatif pour une sortie manuelle
            'reference'  => 'nullable|string',
        ]);

        try {
            // La transaction est importante car le modèle va modifier deux tables (enter_stocks et products)
            $result = DB::transaction(function () use ($request) {

                $movement = EnterStock::create([
                    'product_id' => $request->product_id,
                    'quantity'   => $request->quantity,
                    'created_by' => Auth::id(),
                    'reference'  => $request->reference ?? 'MANUAL_ADJUSTMENT',
                    'status'     => Helper::STATUSSUCCESS, // On valide immédiatement
                ]);

                return $movement;
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès',
                'data'    => $result->load('product')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Historique des mouvements de stock
     */
    public function index(Request $request)
    {
        $query = EnterStock::with(['product', 'creator']) // 'creator' pour éviter le problème N+1
        ->orderBy('created_at', 'desc');

        // Filtre par produit
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filtre par statut (optionnel mais utile)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->paginate($request->query('per_page', 15));

        return response()->json([
            // On transforme les items via la Resource
            'data'         => EnterStockResource::collection($paginator->items()),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }
}
