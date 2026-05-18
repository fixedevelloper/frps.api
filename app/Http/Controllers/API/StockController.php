<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class StockController extends Controller
{
    /**
     * 1. Liste des stocks (Lots disponibles) avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Stock::with('product','movements');

        // Filtrer par produit
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filtrer pour masquer ou afficher les lots vides (quantité à 0)
        if ($request->get('disponibles_only', true)) {
            $query->disponibles();
        }

        // Filtrer les produits proches de la péremption (ex: périme dans moins de 90 jours)
        if ($request->has('alerte_peremption')) {
            $query->where('date_peremption', '<=', now()->addDays(90))
                ->where('date_peremption', '>=', now())
                ->orderBy('date_peremption', 'asc');
        } else {
            // Tri par défaut : Lots reçus récemment
            $query->orderBy('date_reception', 'desc');
        }

        $stocks = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $stocks
        ]);
    }

    /**
     * 2. Entrée en Stock (Création d'un nouveau lot)
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validation stricte des données entrantes
        $validator = Validator::make($request->all(), [
            'product_id'        => 'required|exists:products,id',
            'num_lot'           => 'required|string',
            'date_peremption'   => 'nullable|date|after:today',
            'date_reception'    => 'required|date',
            'quantite_initiale' => 'required|integer|min:1',
            'prix_achat'        => 'required|numeric|min:0',
            'emplacement'       => 'nullable|string',
            'motif'             => 'nullable|string|max:255', // Ajouté à la validation car utilisé plus bas
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // Code 422
        }

        // 2. Récupération exclusive des données validées
        $validatedData = $validator->validated();
        $validatedData['quantite_actuelle'] = $validatedData['quantite_initiale'];

        try {
            // 3. Exécution de la transaction de manière sécurisée
            $stock = DB::transaction(function () use ($validatedData) {

                // Écriture A : Création du lot physique
                $newStock = Stock::create($validatedData);

                // Écriture B : Enregistrement dans le journal des mouvements
                StockMovement::create([
                    'product_id' => $newStock->product_id,
                    'stock_id'   => $newStock->id,
                    'type'       => 'Entrée',
                    'quantite'   => $newStock->quantite_initiale,
                    'user_id'    => auth()->id(),
                    'motif'      => $validatedData['motif'] ?? 'Réception de marchandises - Lot N° ' . $newStock->num_lot
                ]);

                return $newStock;
            });

            // 4. Réponse de succès (Code 201)
            return response()->json([
                'success' => true,
                'message' => 'Nouveau lot enregistré avec succès et tracé dans l\'historique.',
                'data'    => $stock->load('product')
            ], Response::HTTP_CREATED); // Code 201 propre

        } catch (\Exception $e) {
            // En cas de problème d'écriture (BDD indisponible, crash...), la transaction s'annule automatiquement
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'enregistrer l\'entrée en stock.',
                'error'   => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // Code 500
        }
    }

    /**
     * 3. Sortie de Stock avec gestion algorithmique (FIFO / LIFO / FEFO)
     * @param Request $request
     * @return JsonResponse
     */
    public function destock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantite'   => 'required|integer|min:1',
            'strategie'  => 'required|in:FIFO,LIFO,FEFO', // FIFO, LIFO, ou FEFO (Recommandé si denrées/médicaments)
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $productId = $request->product_id;
        $quantiteDemandee = $request->quantite;
        $strategie = $request->strategie;

        // Étape A : Vérifier si la quantité globale disponible est suffisante (tous lots confondus)
        $totalDisponible = Stock::where('product_id', $productId)->disponibles()->sum('quantite_actuelle');

        if ($totalDisponible < $quantiteDemandee) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant. Quantité demandée : $quantiteDemandee, Total disponible en magasin : $totalDisponible."
            ], 400);
        }

        // Étape B : Isoler les lots disponibles et appliquer le tri algorithmique sélectionné
        $lotsQuery = Stock::where('product_id', $productId)->disponibles();

        switch ($strategie) {
            case 'FIFO':
                $lots = $lotsQuery->fifo()->get();
                break;
            case 'LIFO':
                $lots = $lotsQuery->lifo()->get();
                break;
            case 'FEFO':
                $lots = $lotsQuery->fefo()->get();
                break;
        }

        $resteASortir = $quantiteDemandee;
        $lotsImpactes = [];

        // Étape C : Lancement sécurisé de la transaction en base de données
        try {
            DB::beginTransaction();

            foreach ($lots as $lot) {
                if ($resteASortir <= 0) {
                    break;
                }

                $quantitePrelevee = 0;

                if ($lot->quantite_actuelle >= $resteASortir) {
                    // Ce lot possède assez de stock pour finaliser la demande
                    $quantitePrelevee = $resteASortir;
                    $lot->quantite_actuelle -= $resteASortir;
                    $lot->save();

                    $resteASortir = 0;
                } else {
                    // Ce lot n'est pas suffisant : on le vide au maximum et on passe au lot suivant
                    $quantitePrelevee = $lot->quantite_actuelle;
                    $resteASortir -= $lot->quantite_actuelle;
                    $lot->quantite_actuelle = 0;
                    $lot->save();
                }
                StockMovement::create([
                    'product_id' => $productId,
                    'stock_id'   => $lot->id,
                    'type'       => 'Sortie',
                    'quantite'   => $quantitePrelevee,
                    'user_id'    => auth()->id(),
                    'motif'      => $request->motif ?? "Sortie par stratégie " . $strategie
                ]);

                // Pour le rapport de sortie JSON
                $lotsImpactes[] = [
                    'id_lot' => $lot->id,
                    'num_lot' => $lot->num_lot,
                    'quantite_extraite' => $quantitePrelevee,
                    'prix_achat_unitaire' => $lot->prix_achat
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Sortie de stock effectuée avec succès via la méthode $strategie.",
                'details' => [
                    'quantite_totale_sortie' => $quantiteDemandee,
                    'lots_utilises' => $lotsImpactes
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue durant le déstockage.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. Obtenir l'état global du stock pour un produit (Valorisation financière)
     */
    public function productStockStatus($productId): JsonResponse
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produit introuvable.'], 404);
        }

        $lots = Stock::where('product_id', $productId)->disponibles()->get();

        $totalQuantite = $lots->sum('quantite_actuelle');

        // Calcul de la valeur financière du stock restant (Pratique pour la comptabilité en FIFO/LIFO)
        $valeurStock = $lots->sum(function($lot) {
            return $lot->quantite_actuelle * $lot->prix_achat;
        });

        return response()->json([
            'success' => true,
            'product' => $product->intitule,
            'statistiques' => [
                'total_quantite_disponible' => $totalQuantite,
                'nombre_de_lots_actifs' => $lots->count(),
                'valeur_financiere_total_ht' => $valeurStock
            ],
            'lots_en_magasin' => $lots
        ]);
    }
    /**
     * Obtenir l'historique complet des mouvements d'un lot spécifique
     */
    public function getLotHistory($stockId): JsonResponse
    {
        $stock = Stock::with('product')->find($stockId);

        if (!$stock) {
            return response()->json(['success' => false, 'message' => 'Lot introuvable.'], 404);
        }

        $movements = StockMovement::where('stock_id', $stockId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'lot_details' => $stock,
            'historique_mouvements' => $movements
        ]);
    }
    public function ajusterLot(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'quantite_actuelle' => 'required|integer|min:0',
            'motif' => 'required|string',
            'emplacement' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $lot = Stock::findOrFail($request->stock_id);

            // 1. Calculer la différence pour générer le mouvement de stock exact
            $ancienneQuantite = $lot->quantite_actuelle;
            $nouvelleQuantite = $request->quantite_actuelle;
            $difference = $nouvelleQuantite - $ancienneQuantite;

            // 2. Mettre à jour le lot physique
            $lot->quantite_actuelle = $nouvelleQuantite;
            if ($request->has('emplacement')) {
                $lot->emplacement = $request->emplacement;
            }
            $lot->save();

            // 3. Enregistrer le mouvement d'ajustement (Positif ou Négatif)
            if ($difference !== 0) {
                StockMovement::create([
                    'product_id' => $lot->product_id,
                    'stock_id' => $lot->id,
                    'type' => 'Ajustement Inventaire',
                    'quantite' => abs($difference), // Toujours positif dans le champ quantité
                    'motif' => $request->motif . " (Ancien: {$ancienneQuantite} -> Nouveau: {$nouvelleQuantite})",
                    'user_id' => Auth::id()
                ]);
            }
        });

        return response()->json(['message' => 'Success']);
    }
}
