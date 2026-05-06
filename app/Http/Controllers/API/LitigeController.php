<?php

namespace App\Http\Controllers\API;

use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Litige;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LitigeController extends Controller
{


    public function getLitiges(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $litiges = Litige::with([
            'commande.customer.image'
        ])
            ->whereHas('commande', function ($q) {
                $q->where('customer_id', auth()->id());
            })
            ->latest()
            ->paginate($perPage);

        $items = $litiges->getCollection()->map(function ($litige) {

            $customer = $litige->commande?->customer;

            return [
                'id' => $litige->id,
                'order_id' => $litige->commande?->id,
                'type' => $litige->type,
                'description' => $litige->description,

                'status' => $litige->stringStatus->value,
                'status_class' => $litige->stringStatus->class,

                'submitted_at' => $litige->submitted_at,
                'resolution_deadline' => $litige->resolution_deadline,

                'customer_name' => $customer?->name,
                'customer_image' => $customer?->image?->src,
            ];
        });

        return Helpers::success([
            'data' => $items,
            'pagination' => [
                'current_page' => $litiges->currentPage(),
                'last_page' => $litiges->lastPage(),
                'per_page' => $litiges->perPage(),
                'total' => $litiges->total(),
            ]
        ]);
    }
    /**
     * Liste des litiges
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $litiges = Litige::with([
            'commande.customer.image'
        ])   ->latest()
            ->paginate($perPage);

        $items = $litiges->getCollection()->map(function ($litige) {

            $customer = $litige->commande?->customer;

            return [
                'id' => $litige->id,
                'order_id' => $litige->commande?->id,
                'type' => $litige->type,
                'description' => $litige->description,

                'status' => $litige->stringStatus->value,
                'status_class' => $litige->stringStatus->class,

                'submitted_at' => $litige->submitted_at,
                'resolution_deadline' => $litige->resolution_deadline,

                'customer_name' => $customer?->name,
                'customer_image' => $customer?->image?->src,
            ];
        });

        return Helpers::success($items);
    }
    /**
     * Création d'un litige
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:commandes,id',
            'type' => 'required|string',
            'description' => 'required|string',
            'photos.*' => 'image|max:2048'
        ]);

        $photos = [];

        if ($request->hasFile('photos')) {

            foreach ($request->file('photos') as $photo) {

                $path = $photo->store('litiges', 'public');

                $photos[] = $path;
            }
        }

        $litige = Litige::create([
            'commande_id' => $request->order_id,
            'user_id'=>Auth::id(),
            'status' => 'pending',
            'type' => $request->type,
            'description' => $request->description,
            'submitted_at' => now(),
            'resolution_deadline' => now()->addDays(7),
            'photos' => $photos
        ]);

        return Helpers::success($litige);
    }


    /**
     * Détail d'un litige
     */
    public function show(Litige $litige)
    {

        $litige->load('commande.customer.image');

        return Helpers::success([
            'id' => $litige->id,
            'order_id' => $litige->commande?->id,
            'reference' => $litige->commande?->reference,
            'type' => $litige->type,
            'description' => $litige->description,
            'status' => $litige->stringStatus->value,
            'photos' => $litige->photos,
            'submitted_at' => $litige->submitted_at,
            'customer_name' => $litige->user->name
        ]);
    }


    /**
     * Mise à jour d'un litige
     */
    public function update(Request $request, Litige $litige)
    {
        $request->validate([
            'status' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $litige->update([
            'status' => $request->status ?? $litige->status,
            'description' => $request->description ?? $litige->description,
        ]);

        return Helpers::success($litige);
    }


    /**
     * Suppression
     */
    public function destroy(Litige $litige)
    {
        $litige->delete();

        return Helpers::success([
            'message' => 'Litige supprimé'
        ]);
    }
}
