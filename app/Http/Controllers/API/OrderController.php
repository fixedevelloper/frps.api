<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Advantage;
use App\Models\Category;
use App\Models\Commande;
use App\Models\EnterStock;
use App\Models\Litige;
use App\Models\Livraison;
use App\Models\Paiement;
use App\Models\Product;
use App\Models\ProductCommande;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderIssueNotification;
use App\Notifications\ProformaGenerated;
use App\Notifications\ReturnOrderNotification;
use App\Notifications\SmsNotification;
use App\Services\PdfService;
use App\Services\StockService;
use App\Services\TransactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $tranzakService;
    private $pdfService;

    /**
     * OrderController constructor.
     * @param $pdfService
     */
    public function __construct(PdfService $pdfService, TransactService $tranzakService)
    {
        $this->pdfService = $pdfService;
        $this->tranzakService = $tranzakService;
    }

    public function ordersCustomer(Request $request)
    {
        // Récupérer la page et la taille depuis la requête
        $perPage = $request->query('per_page', 10); // par défaut 10 commandes par page
        $page = $request->query('page', 1);

        // Requête avec pagination
        $query = Commande::with([
            'customer.image',
            'products',
            'litiges',
        ])->where('customer_id', auth()->id())
            ->orderBy('created_at', 'desc'); // dernières commandes en premier

        $commandes = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformer les données
        $orders = $commandes->getCollection()->transform(function ($commande) {
            return [
                'id' => $commande->id,
                'reference' => $commande->reference,
                'total' => $commande->total,
                'rest_to_pay' => $commande->rest_to_pay,
                'status' => $commande->stringStatus->value,
                'validatedStatus' => $commande->stringValidatedStatus->value,
                'date' => $commande->created_at,
                'customer_image' => $commande->customer->image ?->src,
                'customer_name' => $commande->customer ?->name,

            // Produits commandés
            'items' => $commande->products->map(function ($item) {
                return [
                    'id' => $item->id,
                    'amount' => $item->amount,
                    'order_id' => $item->commande_id,
                    'product' => $item->product_name ?? 'N/A',
                    'product_price' => $item->product_price,
                    'quantity' => $item->quantity,
                ];
            }),

            // Informations de livraison
/*            'delivery' => $commande->delivery ? [
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

        // Retourner la pagination complète avec les données
        return Helpers::success([
            'data' => $orders,
            'pagination' => [
                'current_page' => $commandes->currentPage(),
                'per_page' => $commandes->perPage(),
                'total' => $commandes->total(),
                'last_page' => $commandes->lastPage(),
            ],
        ]);
    }


    public function orders(Request $request)
    {
        $perPage = $request->input('per_page', 5); // nombre d'éléments par page
        $page = $request->input('page', 1);
        $paginator = Commande::with([
            'customer',
            'products',
            'litiges'
        ])->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $orders = $paginator->through(function ($commande) {
            return [
                'id' => $commande->id,
                'total' => $commande->total,
                'status' => $commande->stringStatus->value,
                'validatedStatus' => $commande->stringValidatedStatus->value,
                'date' => $commande->created_at,
               'customer_image' => $commande->customer->image ?->src,
                'customer_name' => $commande->customer ? $commande->customer->name : null,
                'items' => $commande->products->map(fn($item) => [
                    'id' => $item->id,
                    'amount' => $item->amount,
                    'order_id' => $item->commande_id,
                    'product' => $item->product->intitule ?? 'N/A',
                    'product_price' => $item->product->amount,
                    'quantity' => $item->quantite,
                ]),
                'litiges' => $commande->litiges->map(fn($litige) => [
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
            $user = Auth::user();
            $setting = Setting::query()->first();

            if (!is_null($setting)) {
                // 📧 Mail en queue
                Notification::route('mail', $setting->notification_address)
                    ->notify(new NewOrderNotification($commande));

                // 📲 SMS à l’admin en queue
                /*                $admin = User::query()->firstWhere('phone', $setting->notification_phone);
                                $admin ?->notify(
                                    new SmsNotification("Une nouvelle commande a été créée par $user->first_name. Montant: $commande->total FCFA !")
                                );

                             // 📲 SMS au client en queue
                                $user->notify(
                                    new SmsNotification("Votre commande a été créée avec succès !")
                                );*/
            }


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
            'reference' => $commande->reference,
            'total' => $commande->total,
            'rest_to_pay' => $commande->rest_to_pay,
            'status' => $commande->stringStatus->value,
            'statusValue' => $commande->status,
            'date' => $commande->created_at,
            'customer_image' => $commande->customer->image,
            'facture_pdf'  => $commande->facture_url,
            'proforma_pdf' => $commande->proforma_url,
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
                    'method' => $item->stringMethode->value,
                    'methodClass' => $item->stringMethode->class,
                    'status' => $item->status,
                    'date' => $item->date_paiement,
                ];
            }),
            'advantages' => $commande->customer->advantages
                ->where('active', true)
                ->map(function ($advantage) {

                    return [

                        'id' => $advantage->id,

                        'type' => $advantage->type,

                        'label' => $advantage->label,

                        'description' => $advantage->description,

                        'value' => $advantage->value,

                        'is_percentage' => $advantage->is_percentage,

                        'percentage_paid' => $advantage->percentage_paid,

                        'due_date' => $advantage->due_date,

                        'active' => $advantage->active,

                    ];

                })->values(),
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
            'resolution_deadline' => date('Y-m-d')
        ]);
        $commande = Commande::find($request->order_id);
        $commande->update([
            'status' => Helper::STATUSINVESTIGATION
        ]);

        // Notifier le support
        Notification::route('mail', 'support@frps.com')->notify(new OrderIssueNotification($issue));

        return response()->json(['message' => 'Problème signalé avec succès'], 201);
    }

    public function updateStatusReturn(Request $request)
    {
        // 1. Validation des données entrantes pour correspondre à notre logique Angular
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:return_requests,id', // Remplacez 'return_requests' par le nom de votre table
            'status' => 'required|in:Completed,Rejected',
            'reason' => 'required_if:status,Rejected|nullable|string|min:10'
        ], [
            'id.exists'         => 'Le litige spécifié est introuvable.',
            'status.in'         => 'Le statut doit être "Completed" ou "Rejected".',
            'reason.required_if' => 'Un motif est obligatoire pour refuser un retour.',
            'reason.min'        => 'Le motif de refus doit contenir au moins 10 caractères.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Récupération de la demande de retour
            $returnRequest = ReturnRequest::findOrFail($request->id);

            // 3. Mise à jour des champs
            $returnRequest->status = $request->status;

            // Si c'est un refus, on enregistre le motif, sinon on s'assure qu'il est nul
            if ($request->status === 'Rejected') {
                $returnRequest->reason = $request->reason;
            } else {
                $returnRequest->reason = null;
            }
            $returnRequest->date_traitement = new \DateTime('now');
            $returnRequest->save();

            // 4. (Optionnel) Déclencher un événement ici pour envoyer un mail automatique au client
            // event(new ReturnStatusUpdatedEvent($returnRequest));

            return response()->json([
                'success' => true,
                'message' => 'Le statut du retour a été mis à jour avec succès.',
                'data'    => $returnRequest
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue lors de la mise à jour.',
                'error'   => $e->getMessage()
            ], 500);
        }
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
        $commande = Commande::with([
            'products.product',
            'products.product.stocks'
        ])->findOrFail($id);

        try {
            return DB::transaction(function () use ($commande, $status) {
                switch ($status) {
                    case 2:
                        Notification::route('mail', $commande->customer->email)->notify(new NewOrderNotification($commande));
                        break;

                    case 3:
                        logger($status);
                        $this->generateProformat($commande);
                        if ($commande->customer && $commande->customer->email) {
                            $commande->customer->notify(new ProformaGenerated($commande));
                        }
                        break;
                    case 5:

                        $this->generateFacture($commande);
                        if ($commande->customer && $commande->customer->email) {
                            $commande->customer->notify(new ProformaGenerated($commande));
                        }
                        break;
                    case 7:
                        try {
                            DB::beginTransaction();

                            $serviceStock = app(StockService::class);

                            foreach ($commande->products as $item) {
                                if ($item->product) {
                                    // Récupération de la quantité (s'adapte si c'est une table pivot ou non)
                                    $quantite = $item->pivot->quantite ?? $item->quantite ?? 1;

                                    // Appel de votre service Laravel
                                    $serviceStock->sortirStock(
                                        $item->product,
                                        $quantite,
                                        "Déstockage automatique - Commande #{$commande->id}"
                                    );
                                }
                            }

                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack(); // Annule toutes les sorties de stock si une seule échoue !
                            Log::error("Échec du déstockage de la commande {$commande->id}: " . $e->getMessage());
                            throw $e;
                        }
                        break;
                }

                $commande->update([
                    'status' => $status
                ]);

                return Helpers::success($commande, 'Statut mis à jour avec succès.');
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du changement de statut.',
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    public function generateProformat($commande)
    {
        $directory = public_path('proformas');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "proforma_commande_{$commande->id}_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;

        $pdf = Pdf::loadView('pdf.proforma', [
            'commande' => $commande
        ])->setPaper('A4', 'portrait');

        $pdf->save($fullPath);

        // Sauvegarde du lien en base
        $commande->update([
            'proforma_pdf' => 'proformas/' . $filename
        ]);

        return $fullPath;
    }

    public function paiementFacture(Request $request)
    {
        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. Validation des entrées
            |--------------------------------------------------------------------------
            */
            $request->validate([
                'order_id'     => ['required', 'exists:commandes,id'],
                'amount'       => ['required', 'numeric', 'min:0'],
                'methodPayment'=> ['required'],
                'advantage_id' => ['nullable', 'exists:advantages,id']
            ]);

            $commande = Commande::findOrFail($request->order_id);

            /*
            |--------------------------------------------------------------------------
            | 2. Vérification d'avantage existant (Contrainte demandée)
            |--------------------------------------------------------------------------
            | On vérifie si la commande a déjà un avantage lié dans la table pivot
            */
            if ($request->filled('advantage_id')) {
                $hasAdvantage = DB::table('commande_advantages')
                    ->where('commande_id', $commande->id)
                    ->exists();

                if ($hasAdvantage) {
                    return Helpers::error("Cette commande bénéficie déjà d'un avantage. Les remises ne sont pas cumulables.");
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Initialisation des variables de calcul
            |--------------------------------------------------------------------------
            */
            $montantInitial = (float) $commande->rest_to_pay;
            $discount       = 0;
            $montantFinal   = $montantInitial;
            $advantage      = null;

            /*
            |--------------------------------------------------------------------------
            | 4. Logique de calcul de l'avantage
            |--------------------------------------------------------------------------
            */
            if ($request->filled('advantage_id')) {
                $advantage = Advantage::findOrFail($request->advantage_id);

                if (!$advantage->active) {
                    return Helpers::error("Cet avantage est actuellement désactivé.");
                }

                switch ($advantage->type) {
                    case 'remise':
                        $discount = $advantage->is_percentage
                            ? ($montantInitial * $advantage->value) / 100
                            : $advantage->value;
                        $montantFinal = $montantInitial - $discount;
                        break;

                    case 'bon_reduction':
                        $discount = $advantage->value;
                        $montantFinal = $montantInitial - $discount;
                        break;

                    case 'paiement_differe':
                        // Ici on calcule ce qui doit être payé immédiatement
                        $montantFinal = ($montantInitial * $advantage->percentage_paid) / 100;
                        // Le reste reste dans 'rest_to_pay' après soustraction du montant payé
                        break;
                }
            }

            // Sécurité : Le montant final ne peut pas être négatif
            $montantFinal = max($montantFinal, 0);

            /*
            |--------------------------------------------------------------------------
            | 5. Vérification de l'intégrité du montant
            |--------------------------------------------------------------------------
            */
            if (round((float)$request->amount, 2) !== round($montantFinal, 2)) {
               // return Helpers::error("Le montant envoyé ({$request->amount}) ne correspond pas au calcul attendu ({$montantFinal}).");
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Initialisation du paiement Tranzak
            |--------------------------------------------------------------------------
            */
            $reference = 'FRPS-' . Str::upper(Str::random(10));

            $response = $this->tranzakService->makeColletion([
                'amount'      => $montantFinal,
                'reference'   => $reference,
                'success_url' => url('/payment/success'),
                'cancel_url'  => url('/payment/cancel'),
                'callback_url'=> url('/tranzak/webhook'),
                'description' => "Paiement commande #{$commande->id}"
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. Enregistrement et mise à jour
            |--------------------------------------------------------------------------
            */
            $paiement = Paiement::create([
                'reference'       => $response['data']['requestId'] ?? $reference,
                'commande_id'     => $commande->id,
                'montant'         => $montantFinal,
                'methode'         => $request->methodPayment,
                'status'          => 'pending',
                'etat'            => Helper::PAIEMENTETATCOMPLET,
                'date_paiement'   => now(),
                'advantage_id'    => $advantage?->id,
            'discount_amount' => $discount
        ]);

        // Mise à jour du reste à payer sur la commande
        $nouveauReste = max($commande->rest_to_pay - $montantFinal - $discount, 0);

        $commande->update([
            'status'          => Helper::STATUSPROCESSING,
            'rest_to_pay'     => $nouveauReste,
            'discount_amount' => $commande->discount_amount + $discount
        ]);

        // Historique de l'avantage (Table Pivot)
        if ($advantage) {
            $commande->advantages()->attach($advantage->id, [
                'amount' => $discount,
                'created_at' => now()
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Bordereau (si premier paiement)
        |--------------------------------------------------------------------------
        */
        if (($commande->total == $commande->rest_to_pay + $montantFinal + $discount)) {
            $this->generateBordereau($commande);
        }

        DB::commit();

        return Helpers::success([
            'url'       => $response['data']['links']['paymentAuthUrl'],
            'amount'    => $montantFinal,
            'discount'  => $discount,
            'remaining' => $nouveauReste
        ]);

    } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Paiement: " . $e->getMessage());
            return Helpers::error("Erreur système : " . $e->getMessage());
        }
    }

    public function paiementFactureMobile(Request $request)
    {
        // 1. Validation des entrées (En dehors de la transaction)
        $request->validate([
            'order_id'      => ['required', 'exists:commandes,id'],
            'amount'        => ['required', 'numeric', 'min:0'],
            'methodPayment' => ['required'],
            'advantage_id'  => ['nullable', 'exists:advantages,id']
        ]);

        $commande = Commande::findOrFail($request->order_id);

        // Cas spécifique (par exemple: Cash/Test) - Pas besoin de transaction ici
        if ($request->methodPayment == 4) {
            return Helpers::success([
                'mode'      => $request->methodPayment,
                'amount'    => $commande->total,
                'discount'  => 0.0,
                'remaining' => 0.0
            ]);
        }

        // 2. Vérification d'avantage existant
        if ($request->filled('advantage_id')) {
            $hasAdvantage = DB::table('commande_advantages')
                ->where('commande_id', $commande->id)
                ->exists();

            if ($hasAdvantage) {
                return Helpers::error("Cette commande bénéficie déjà d'un avantage. Les remises ne sont pas cumulables.");
            }
        }

        // 3. Initialisation des variables de calcul
        $montantInitial = (float) $commande->rest_to_pay;
        $discount       = 0.0;
        $montantFinal   = $montantInitial;
        $advantage      = null;

        // 4. Logique de calcul de l'avantage
        if ($request->filled('advantage_id')) {
            $advantage = Advantage::findOrFail($request->advantage_id);

            if (!$advantage->active) {
                return Helpers::error("Cet avantage est actuellement désactivé.");
            }

            switch ($advantage->type) {
                case 'remise':
                    $discount = $advantage->is_percentage
                        ? ($montantInitial * $advantage->value) / 100
                        : (float) $advantage->value;
                    $montantFinal = $montantInitial - $discount;
                    break;

                case 'bon_reduction':
                    $discount = (float) $advantage->value;
                    $montantFinal = $montantInitial - $discount;
                    break;

                case 'paiement_differe':
                    $montantFinal = ($montantInitial * $advantage->percentage_paid) / 100;
                    // Note : Le discount reste 0 car la somme restante sera payée plus tard
                    break;
            }
        }

        // Sécurité : Le montant final ne peut pas être négatif
        $montantFinal = max($montantFinal, 0);

        // 5. Vérification de l'intégrité du montant
        if (round((float)$request->amount, 2) !== round($montantFinal, 2)) {
            return Helpers::error("Le montant envoyé ({$request->amount}) ne correspond pas au calcul attendu ({$montantFinal}).");
        }

        // 6. Initialisation du paiement Tranzak
        $reference = 'FRPS-' . Str::upper(Str::random(10));
        $url = URL::route('payment.pay', ['reference' => $reference]);

        // 7. Début de la transaction UNIQUEMENT pour les écritures en BDD
        DB::beginTransaction();

        try {
            $paiement = Paiement::create([
                'reference'       => $reference,
                'commande_id'     => $commande->id,
                'montant'         => $montantFinal,
                'methode'         => $request->methodPayment,
                'status'          => 'pending', // Reste en pending jusqu'au webhook Tranzak
                'etat'            => Helpers::PAIEMENTETATCOMPLET, // Attention au nom de votre classe (Helpers vs Helper)
                'date_paiement'   => now(),
                'advantage_id'    => $advantage?->id,
            'discount_amount' => $discount
        ]);

        // ATTENTION : Mettre à jour le reste à payer alors que le statut est "pending"
        // peut être risqué si l'utilisateur annule le paiement sur la page Tranzak.
        $nouveauReste = max($commande->rest_to_pay - $montantFinal - $discount, 0);

        $commande->update([
            'status'          => Helpers::STATUSPROCESSING,
            'rest_to_pay'     => $nouveauReste,
            'discount_amount' => $commande->discount_amount + $discount
        ]);

        // Historique de l'avantage (Table Pivot)
        if ($advantage) {
            $commande->advantages()->attach($advantage->id, [
                'amount'     => $discount,
                'created_at' => now()
            ]);
        }

        // 8. Bordereau (si premier paiement) - Correction de la comparaison des floats
        $totalCalcule = round($commande->rest_to_pay + $montantFinal + $discount, 2);
        if (round($commande->total, 2) === $totalCalcule) {
            $this->generateBordereau($commande);
        }

        DB::commit();

        return Helpers::success([
            'mode'      => $request->methodPayment,
            'url'       => $url,
            'amount'    => $montantFinal,
            'discount'  => $discount,
            'remaining' => $nouveauReste
        ]);

    } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Paiement Code: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Helpers::error("Erreur système lors de l'enregistrement du paiement.");
        }
    }
    public function paiementFactureAdmin(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'order_id'     => ['required', 'exists:commandes,id'],
                'amount'       => ['required', 'numeric', 'min:1'], // On ne paie pas 0
                'advantage_id' => ['nullable', 'exists:advantages,id']
            ]);

            $commande = Commande::lockForUpdate()->findOrFail($request->order_id);

            // 1. Vérification des avantages cumulés
            if ($request->filled('advantage_id')) {
                $hasAdvantage = DB::table('commande_advantages')
                    ->where('commande_id', $commande->id)
                    ->exists();

                if ($hasAdvantage) {
                    return Helpers::error("Cette commande bénéficie déjà d'un avantage.");
                }
            }

            $montantInitial = (float) $commande->rest_to_pay;
            $discount       = 0;
            $advantage      = null;

            // 2. Calcul de la remise si applicable
            if ($request->filled('advantage_id')) {
                $advantage = Advantage::findOrFail($request->advantage_id);
                if (!$advantage->active) return Helpers::error("Avantage désactivé.");

                if ($advantage->type === 'remise') {
                    $discount = $advantage->is_percentage
                        ? ($montantInitial * $advantage->value) / 100
                        : $advantage->value;
                } elseif ($advantage->type === 'bon_reduction') {
                    $discount = $advantage->value;
                }
            }

            // 3. Validation du montant envoyé par le frontend
            // Le montant envoyé doit être ce que le client donne réellement en cash.
            $montantRecu = (float) $request->amount;

            if ($montantRecu > ($montantInitial - $discount)) {
                return Helpers::error("Le montant dépasse le reste à payer (Max attendu: " . ($montantInitial - $discount) . ")");
            }

            // 4. Enregistrement du Paiement
            $reference = 'CASH-' . Str::upper(Str::random(10));

            $paiement = Paiement::create([
                'reference'       => $reference,
                'commande_id'     => $commande->id,
                'montant'         => $montantRecu,
                'methode'         => Helper::METHODCASH, // Plus explicite que "4"
                'status'          => 'paid',
                'etat'            => Helper::PAIEMENTETATCOMPLET,
                'date_paiement'   => now(),
                'advantage_id'    => $advantage?->id,
            'discount_amount' => $discount
        ]);

        // 5. Mise à jour de la commande
        $nouveauReste = max($commande->rest_to_pay - $montantRecu - $discount, 0);

        // La commande ne passe en SUCCESS que si tout est payé
        $nouveauStatut = ($nouveauReste <= 0) ? Helper::STATUSSUCCESS : $commande->status;

        $commande->update([
            'status'          => $nouveauStatut,
            'rest_to_pay'     => $nouveauReste,
            'discount_amount' => $commande->discount_amount + $discount
        ]);

        // 6. Historique Pivot
        if ($advantage && $discount > 0) {
            $commande->advantages()->attach($advantage->id, ['amount' => $discount]);
        }

        // 7. Génération de facture automatique si solde à zéro
        if ($nouveauReste <= 0) {
            $this->generateFacture($commande);
        }

        DB::commit();

        return Helpers::success([
            'payment'   => $paiement,
            'remaining' => $nouveauReste,
            'status'    => $nouveauStatut
        ], "Paiement cash enregistré avec succès.");

    } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::error("Erreur système : " . $e->getMessage());
        }
    }

    public function generateBordereau($commande)
    {
        $directory = public_path('bordereaux');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "bordereau_commande_{$commande->id}_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;

        $pdf = Pdf::loadView('pdf.bordereau', [
            'commande' => $commande
        ])->setPaper('A4', 'portrait');

        $pdf->save($fullPath);

        $commande->update([
            'bordereau_pdf' => 'bordereaux/' . $filename
        ]);

        return $fullPath;
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

    public function getReturns(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $returns = ReturnRequest::with([
            'commande.customer.image',
            'productOrder', 'productOrder.product'
        ])->latest()->paginate($perPage);

        $items = $returns->getCollection()->map(function ($returnRequest) {

            $customer = $returnRequest->commande ?->customer;

        return [
            'id' => $returnRequest->id,
            'order_id' => $returnRequest->commande ?->id,
            'product_order_id' => $returnRequest->product_order_id,

            'reason' => $returnRequest->reason,
            'status' => $returnRequest->status,

            'date_demande' => $returnRequest->date_demande,
            'date_traitement' => $returnRequest->date_traitement,
           'product' => $returnRequest->productOrder->product,
            'customer_image' => $customer ?->image ?->src,
            'customer_name' => $customer ?->name,
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

    public function generateFacture($commande, $avecTVA = false)
    {
        $directory = public_path('factures');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "facture_{$commande->id}_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;

        $pdf = Pdf::loadView('pdf.facture', [
            'commande' => $commande,
            'avecTVA' => $avecTVA
        ])->setPaper('A4', 'portrait');

        $pdf->save($fullPath);

        $commande->update([
            'facture_pdf' => 'factures/' . $filename
        ]);

        return $fullPath;
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer|exists:product_commande,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = ProductCommande::findOrFail($request->item_id);

        $item->quantite = $request->quantity;
        $item->save();

        // recalcul total ligne
        $item->amount = $item->quantite * $item->product->price;
        // $item->save();

        // optionnel: recalcul total commande
        $order = $item->commande;
        $order->total = $order->products->sum(function ($i) {
            return $i->quantite * $i->product->price;
        });
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => $item
        ]);
    }
}
