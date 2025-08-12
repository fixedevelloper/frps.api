<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Litige;
use App\Models\Paiement;
use App\Models\Product;
use App\Models\ReturnRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{

    public function getLitiges(Request $request)
    {
        $litiges = Litige::with([
            'customer',
        ])->leftJoin('litiges.commande_id','commandes.id')->leftJoin('commandes.customer_id','users.id')->where('commandes.customer_id', auth()->id())->get();

        $items = $litiges->map(function ($payment) {
            return [
                'id' => $payment->id,
                'order_id' => $payment->commande->id,
                'montant' => $payment->montant,
                'status' => $payment->stringStatus->value,
                'date' => $payment->created_at,
                'customer_image' => $payment->commande->customer->image ? $payment->commande->customer->image->src : null,
                'customer_name' => $payment->commande->customer
                    ? $payment->commande->customer->name
                    : null,
            ];
        });

        return Helpers::success($items);
    }
    public function getReturns(Request $request)
    {
        $returns = ReturnRequest::with([
            'customer',
        ])->where('customer_id', auth()->id())->get();

        $items = $returns->map(function ($payment) {
            return [
                'id' => $payment->id,
                'order_id' => $payment->commande->id,
                'montant' => $payment->montant,
                'status' => $payment->stringStatus->value,
                'date' => $payment->created_at,
                'customer_image' => $payment->commande->customer->image ? $payment->commande->customer->image->src : null,
                'customer_name' => $payment->commande->customer
                    ? $payment->commande->customer->name
                    : null,
            ];
        });

        return Helpers::success($items);
    }
    public function index(Request $request)
    {
        $query = Product::query();

        // Pagination params (pageSize par défaut)
        $pageSize = $request->query('pageSize', 10);
        $page = $request->query('page', 1);

        // Filtres
        if ($request->has('category')) {
            $query->where('category_id', $request->query('category'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('intitule', 'LIKE', "%{$search}%");
        }

        if ($request->has('priceRange')) {
            $prices = explode(',', $request->query('priceRange'));
            if (count($prices) === 2) {
                $min = floatval($prices[0]);
                $max = floatval($prices[1]);
                $query->whereBetween('price', [$min, $max]);
            }
        }

        // Paginate avec pageSize et page demandés
        $products = $query->paginate($pageSize, ['*'], 'page', $page);

        return Helpers::success([
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'data' => $products->items(),
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
            'email' => 'required|email|unique:clients,email,' . $client->id,
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
}
