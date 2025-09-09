<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commande;
use App\Models\Litige;
use App\Models\Livraison;
use App\Models\Paiement;
use App\Models\Product;
use App\Models\ProductCommande;
use App\Models\ReturnRequest;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderIssueNotification;
use App\Notifications\ProformaGenerated;
use App\Notifications\ReturnOrderNotification;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private $pdfService;

    /**
     * OrderController constructor.
     * @param $pdfService
     */
    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function ordersCustomer(Request $request)
    {
        $commandes = Commande::with([
            'customer',
            'products',
            'litiges'
        ])->where('customer_id', auth()->id())->get();

        $orders = $commandes->map(function ($commande) {
            return [
                'id' => $commande->id,
                'total' => $commande->total,
                'status' => $commande->stringStatus->value,
                'validatedStatus' => $commande->stringValidatedStatus->value,
                'date' => $commande->created_at,
                'customer_image' => $commande->customer->image ? $commande->customer->image->src : null,
                'customer_name' => $commande->customer
                    ? $commande->customer->name
                    : null,

                // Produits commandés
                'items' => $commande->products->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'amount' => $item->amount,
                        'order_id' => $item->commande_id,
                        'product' => $item->product_name ?? 'N/A', // adapte si relation
                        'product_price' => $item->product_price,
                        'quantity' => $item->quantity,
                    ];
                }),

                // Informations de livraison
/*                'delivery' => $commande->delivery ? [
                    'id' => $commande->delivery->id,
                    'status' => $commande->delivery->status,
                    'delivered_at' => $commande->delivery->delivered_at,
                    'address' => $commande->delivery->address,
                ] : null,*/

                // Litiges associés
                'litiges' => $commande->litiges->map(function ($litige) {
                    return [
                        'id' => $litige->id,
                        'motif' => $litige->motif,
                        'status' => $litige->status,
                        'commentaire' => $litige->commentaire,
                        'created_at' => $litige->created_at,
                    ];
                }),
            ];
        });

        return Helpers::success($orders);
    }


    public function orders(Request $request)
    {
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1);
        $paginator = Commande::with([
            'customer',
            'products',
            'litiges'
        ])->paginate($perPage, ['*'], 'page', $page);

        $orders = $paginator->through(function ($commande) {
            return [
                'id' => $commande->id,
                'total' => $commande->total,
                'status' => $commande->stringStatus->value,
                'validatedStatus' => $commande->stringValidatedStatus->value,
                'date' => $commande->created_at,
                'customer_image' => $commande->customer,
                'customer_name' => $commande->customer ? $commande->customer->name : null,
                'items' => $commande->products->map(fn ($item) => [
                    'id' => $item->id,
                    'amount' => $item->amount,
                    'order_id' => $item->commande_id,
                    'product' => $item->product_name ?? 'N/A',
                    'product_price' => $item->product_price,
                    'quantity' => $item->quantity,
                ]),
                'litiges' => $commande->litiges->map(fn ($litige) => [
                    'id' => $litige->id,
                    'motif' => $litige->motif,
                    'status' => $litige->status,
                    'commentaire' => $litige->commentaire,
                    'created_at' => $litige->created_at,
                ]),
            ];
        });

        return Helpers::success([
            'data' => $orders->items(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);

    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.productId' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Calcul du total
            $total = 0;
            foreach ($request->products as $prod) {
                $product = Product::findOrFail($prod['productId']);
                $total += $product->price * $prod['quantity'];
            }
            $now = new \DateTime('now');
            //$inter=new \DateInterval('d');
            // Création de la commande
            $commande = Commande::create([
                'customer_id' => auth()->id(),
                'total' => $total,
                'status' => Helper::STATUSPENDING,
                'validatedStatus' => Helper::STATUSPENDING,
                'date_validation' => date('Y-m-d'),
                'timer_auto' => $now->add(new \DateInterval('P3D')),
                'rest_to_pay' => $total,
                'reference' => Helper::generateReference()
            ]);

            // Ajout des produits dans la table pivot
            foreach ($request->products as $prod) {
                $product = Product::findOrFail($prod['productId']);

                ProductCommande::create([
                    'commande_id' => $commande->id,
                    'product_id' => $product->id,
                    'quantite' => $prod['quantity'],
                    'product_price' => $product->price,
                    'amount' => $prod['quantity'] * $product->price,
                ]);
            }
            Notification::route('mail', 'support@frps.com')->notify(new NewOrderNotification($commande));
            DB::commit();

            return Helpers::success([
                'message' => 'Commande enregistrée avec succès.',
                'commande_id' => $commande->id
            ]);
        } catch (\Exception $e) {
            logger($e->getMessage());
            DB::rollback();
            return Helpers::error('Erreur lors de l\'enregistrement de la commande', 500, $e->getMessage());
        }
    }

    public function orderDetail(Request $request, $id)
    {
        $commande = Commande::with([
            'customer',
            'products',
            'transporteur',
            'litiges'
        ])->find($id);

        if (!$commande) {
            return Helpers::error('Commande non trouvée', 404);
        }

        $order = [
            'id' => $commande->id,
            'total' => $commande->total,
            'status' => $commande->stringStatus->value,
            'statusValue' => $commande->status,
            'date' => $commande->created_at,
            'customer_image' => $commande->customer->image,
            'facture_pdf' => $commande->facture_pdf,
            'proforma_pdf' => config('app.url') . $commande->proforma_pdf,
            'customer_name' => $commande->customer
                ? $commande->customer->name
                : null,

            // Produits commandés
            'items' => $commande->products->map(function ($item) {
                return [
                    'id' => $item->id,
                    'amount' => $item->amount,
                    'order_id' => $item->commande_id,
                    'product' => $item->product ? $item->product->intitule : 'N/A',
                    'product_price' => $item->product ? $item->product->price : 'N/A',
                    'quantity' => $item->quantite,
                ];
            }),


            'delivery' => $commande->transporteur ? [
                'id' => $commande->transporteur->id,
                'type' => $commande->transporteur->type,
                'delivered_at' => $commande->transporteur->delivered_at,
                'name' => $commande->transporteur->nom,
            ] : null,

            // Litiges associés
            'litiges' => $commande->litiges->map(function ($litige) {
                return [
                    'id' => $litige->id,
                    'motif' => $litige->motif,
                    'status' => $litige->status,
                    'commentaire' => $litige->commentaire,
                    'created_at' => $litige->created_at,
                ];
            }),
            'payments' => $commande->paiement->map(function ($item) {
                return [
                    'id' => $item->id,
                    'amount' => $item->montant,
                    'order_id' => $item->commande_id,
                    'method' => $item->stringMethode->value ,
                    'status' => $item->etat ,
                    'date' => $item->date_paiement,
                ];
            }),
        ];

        return Helpers::success($order);
    }

    public function storeLitige(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:commandes,id',
            'type' => 'required|in:retard,colis_endommage,non_conformite,produit_defectueux,erreur_livraison,quantite_incorrecte',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|max:2048',
        ]);

        $photos = [];
        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $photo) {
                $photos[] = $photo->store('litiges', 'public');
            }
        }

        $issue = Litige::create([
            'commande_id' => $request->order_id,
            'type' => $request->type,
            'description' => $request->description,
            'photos' => json_encode($photos),
            'status' => 'en_investigation',
            'resolution_deadline'=>date('Y-m-d')
        ]);
        $commande=Commande::find($request->order_id);
        $commande->update([
           'status'=>Helper::STATUSINVESTIGATION
        ]);

        // Notifier le support
        Notification::route('mail', 'support@frps.com')->notify(new OrderIssueNotification($issue));

        return response()->json(['message' => 'Problème signalé avec succès'], 201);
    }

    public function storeReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:commandes,id',
            'order_item_id' => 'required|exists:product_commande,id',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Crée l'objet ReturnRequest
        $returnRequest = new ReturnRequest();
        $returnRequest->commande_id = $request->order_id;
        $returnRequest->product_order_id = $request->order_item_id;
        $returnRequest->reason = $request->reason;
        $returnRequest->status = 'en attente'; // statut par défaut
        $returnRequest->save();

        // Upload des photos si présentes
        if ($request->hasFile('proofs')) {
            $paths = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('returns/photos', 'public');
                $paths[] = $path;
            }
            $returnRequest->photos = json_encode($paths);
            $returnRequest->save();
        }
/*        $notification=\App\Models\Notification::create([
            'type'=>\App\Models\Notification::ORDERTYPE,
        ]);*/
        // Notification éventuelle (ex: support)
        Notification::route('mail', 'support@frps.com')
            ->notify(new ReturnOrderNotification($returnRequest));

        return Helpers::success($returnRequest, 'Demande de retour enregistrée avec succès.');
    }
    public function assignTransporteur(Request $request, $id)
    {
        $request->validate([
            'transporteur_id' => 'required|exists:transporteurs,id'
        ]);

        $commande = Commande::findOrFail($id);
        $commande->transporteur_id = $request->transporteur_id;
        $commande->save();

        return response()->json([
            'message' => 'Transporteur assigné avec succès',
            'commande' => $commande
        ]);
    }

    public function getByOrder($orderId)
    {
        return Litige::where('order_id', $orderId)->get();
    }

    public function changeStatus(Request $request, $id, $status)
    {
        $commande = Commande::findOrFail($id);

        switch ($status){
            case 3:
                Notification::route('mail', $commande->customer->email)->notify(new NewOrderNotification($commande));
            case 4:
                $this->pdfService->generateProformat($commande);
                if ($commande->customer && $commande->customer->email) {
                    $commande->customer->notify(new ProformaGenerated($commande));
                }

        }
        $commande->update([
            'status' => $status
        ]);

        return Helpers::success($commande, 'Statut mis à jour avec succès.');
    }


    public function paiementFacture(Request $request)
    {
        DB::beginTransaction();

        try {
            $commande = Commande::findOrFail($request->order_id);

            // Montant déjà payé avant ce paiement
            $totalPayeAvant = $commande->total - $commande->rest_to_pay;

            // Vérification : empêcher de dépasser le total
            if ($totalPayeAvant + $request->amount > $commande->total) {
                return Helpers::error("Le montant payé dépasse le total de la commande.");
            }

            // Création du paiement
            $paiement = Paiement::create([
                'commande_id'   => $request->order_id,
                'montant'       => $request->amount,
                'methode'       => $request->methodPayment,
                'etat'          => Helper::PAIEMENTETATCOMPLET,
                'date_paiement' => date('Y-m-d')
            ]);

            // Mise à jour du montant restant
            $nouveauReste = $commande->rest_to_pay - $request->amount;
            $commande->update([
                'status'      => Helper::STATUSPROCESSING,
                'rest_to_pay' => max($nouveauReste, 0)
            ]);

            // Générer bordereau seulement au premier paiement
            if ($totalPayeAvant == 0) {
                $this->pdfService->generateBordereau($commande);
            }

            DB::commit();

            return Helpers::success($commande);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::error("Erreur lors du paiement : " . $e->getMessage());
        }
    }


    public function paiementCustomer(Request $request)
    {
        $paiements = Paiement::with([
            'customer',
        ])->where('customer_id', auth()->id())->get();

        $orders = $paiements->map(function ($payment) {
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

        return Helpers::success($orders);
    }

    public function getLitiges(Request $request)
    {
        $litiges = Litige::with([
            'customer',
        ])->get();

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
        ])->get();

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
    public function traiterLitige(Request $request, $litigeId)
    {
        $litige = Litige::findOrFail($litigeId);

        $request->validate([
            'statut' => 'required|in:valide,rejete',
            'commentaire' => 'nullable|string',
            'solution' => 'nullable|in:remboursement,echange',
        ]);

        $litige->statut = $request->statut;
        $litige->commentaire = $request->commentaire;

        if ($request->statut === 'valide') {
            $litige->solution = $request->solution;
            // Logique de remboursement ou échange ici...
        }

        $litige->save();

        // Notification à l'utilisateur FOSA
       // Notification::send($litige->commande->user, new LitigeTraiteNotification($litige));

        return response()->json(['message' => 'Litige traité avec succès']);
    }

}
