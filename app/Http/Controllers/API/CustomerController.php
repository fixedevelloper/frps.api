<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Litige;
use App\Models\Paiement;
use App\Models\Product;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{

    public function getLitiges(Request $request)
    {
        $litiges = Litige::with('commande.customer.image')
            ->whereHas('commande', fn($q) => $q->where('customer_id', auth()->id()))
            ->latest()
            ->paginate(10);

        $items = $litiges->map(function ($litige) {

            $customer = $litige->commande?->customer;

        return [
            'id' => $litige->id,
            'order_id' => $litige->commande?->id,
            'type' => $litige->type,
            'description' => $litige->description,

            'status' => $litige->stringStatus->value,
            'status_class' => $litige->stringStatus->class,

            'submitted_at' => $litige->submitted_at,

            'customer_image' => $customer?->image?->src,
            'customer_name' => $customer?->name,
        ];
    });

        return Helpers::success($items);
    }
    public function getReturns(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $returns = ReturnRequest::with([
            'commande.customer.image','productOrder.product'
        ])
            ->whereHas('commande', function ($q) {
                $q->where('customer_id', auth()->id());
            })
            ->latest()
            ->paginate($perPage);

        $items = $returns->getCollection()->map(function ($return) {

            $customer = $return->commande?->customer;

        return [
            'id' => $return->id,
            'order_id' => $return->commande?->id,
            'reason' => $return->reason,
            'status' => $return->status,
            'product' => $return->productOrder->product,
            'date_demande' => $return->date_demande,
            'date_traitement' => $return->date_traitement,

            'customer_image' => $customer?->image?->src,
            'customer_name' => $customer?->name,
        ];
    });

        return Helpers::success([
            'data' => $items,
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ]
        ]);
    }
    public function index(Request $request)
    {
        // On charge les relations utiles (category, image) pour éviter le problème N+1
        $query = Product::with(['category', 'image'])->where('publish', true);

        /*
        |--------------------------------------------------------------------------
        | Filtres
        |--------------------------------------------------------------------------
        */

        // Recherche textuelle
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('intitule', 'LIKE', "%{$search}%");
        }

        // Filtre par catégorie
        if ($request->filled('category')) {
            $query->where('category_id', $request->query('category'));
        }

        // Filtre par plage de prix
        if ($request->filled('priceRange')) {
            $prices = explode(',', $request->query('priceRange'));
            if (count($prices) === 2) {
                $query->whereBetween('price', [floatval($prices[0]), floatval($prices[1])]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Tri et Pagination
        |--------------------------------------------------------------------------
        */

        // Ajout du tri par nom (A-Z)
        $query->orderBy('intitule', 'asc');

        $pageSize = $request->query('pageSize', 10);
        $page = $request->query('page', 1);

        $products = $query->paginate($pageSize, ['*'], 'page', $page);

        return Helpers::success([
            'total'        => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'per_page'     => $products->perPage(),
            'data'         => ProductResource::collection($products->items()),
        ]);
    }
    // Récupérer infos du client connecté
    public function getInfo()
    {
        $client = Auth::user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'departement_id' => $client->departement_id,
                'city_id' => $client->city_id,
                'balance' => $client->balance,
                'debt' => $client->debt,
            ]
        ]);
    }
    // Mettre à jour infos client
    public function updateInfo(Request $request)
    {
        $client = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email,' . $client->id,
            'phone' => 'required|string',
            'departement_id' => 'required|integer',
            'city_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $client->update($request->only([
            'name',
            'email',
            'phone',
            'departement_id',
            'city_id',
        ]));


        return Helpers::success($client,'Informations mises à jour avec succès');
    }
    public function destroyReturn(ReturnRequest $returnRequest)
    {
        $returnRequest->delete();

        return Helpers::success([
            'message' => 'Return supprimé'
        ]);
    }
}
