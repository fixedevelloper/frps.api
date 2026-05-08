<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Advantage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdvantageController extends Controller
{
    public function customerAdvantages($id)
    {
        $advantages = Advantage::where('customer_id', $id)
            ->where('active', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $advantages
        ]);
    }
    /**
     * Liste des avantages
     */
    public function index(Request $request): JsonResponse
    {
        $query = Advantage::with('customer')
            ->latest();

        // Filtre par client
        if ($request->filled('customer_id')) {

            $query->where(
                'customer_id',
                $request->customer_id
            );

        }

        // Filtre actif/inactif
        if ($request->filled('active')) {

            $query->where(
                'active',
                $request->boolean('active')
            );

        }

        $advantages = $query->paginate(
            $request->per_page ?? 10
        );

        return response()->json($advantages);
    }

    /**
     * Ajouter un avantage
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([

            'customer_id' => [
                'required',
                'exists:users,id'
            ],

            'type' => [
                'required',
                'in:paiement_differe,remise,bon_reduction'
            ],

            // montant remise / bon
            'value' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            // remise %
            'is_percentage' => [
                'nullable',
                'boolean'
            ],

            // paiement différé
            'percentage_paid' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100'
            ],

            'due_date' => [
                'nullable',
                'date'
            ],

            'label' => [
                'nullable',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'active' => [
                'nullable',
                'boolean'
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Valeurs par défaut
        |--------------------------------------------------------------------------
        */

        $data['value'] =
            $data['value'] ?? 0;

        $data['is_percentage'] =
            $data['is_percentage'] ?? true;

        $data['percentage_paid'] =
            $data['percentage_paid'] ?? 100;

        $data['active'] =
            $data['active'] ?? true;

        /*
        |--------------------------------------------------------------------------
        | Création
        |--------------------------------------------------------------------------
        */

        $advantage = Advantage::create($data);

        return response()->json([

            'success' => true,

            'message' =>
                'Avantage ajouté avec succès',

            'data' => $advantage

        ], 201);
    }

    /**
     * Détail avantage
     */
    public function show(int $id): JsonResponse
    {
        $advantage = Advantage::with('customer')
            ->findOrFail($id);

        return response()->json([

            'success' => true,

            'data' => $advantage

        ]);
    }

    /**
     * Modifier avantage
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {

        $advantage = Advantage::findOrFail($id);

        $data = $request->validate([

            'type' => [
                'sometimes',
                'in:paiement_differe,remise,bon_reduction'
            ],

            'value' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'is_percentage' => [
                'nullable',
                'boolean'
            ],

            'percentage_paid' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100'
            ],

            'due_date' => [
                'nullable',
                'date'
            ],

            'label' => [
                'nullable',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'active' => [
                'nullable',
                'boolean'
            ],

        ]);

        $advantage->update($data);

        return response()->json([

            'success' => true,

            'message' =>
                'Avantage modifié avec succès',

            'data' => $advantage

        ]);
    }

    /**
     * Activer / désactiver
     */
    public function toggle(int $id): JsonResponse
    {
        $advantage = Advantage::findOrFail($id);

        $advantage->active =
            !$advantage->active;

        $advantage->save();

        return response()->json([

            'success' => true,

            'message' => $advantage->active
                ? 'Avantage activé'
                : 'Avantage désactivé',

            'data' => $advantage

        ]);
    }

    /**
     * Supprimer
     */
    public function destroy(int $id): JsonResponse
    {
        $advantage = Advantage::findOrFail($id);

        $advantage->delete();

        return response()->json([

            'success' => true,

            'message' =>
                'Avantage supprimé avec succès'

        ]);
    }

    /**
     * Calcul avantage
     */
    public function calculate(
        float $amount,
        Advantage $advantage
    ): array {

        $discount = 0;

        $finalAmount = $amount;

        switch ($advantage->type) {

            case 'remise':

                if ($advantage->is_percentage) {

                    $discount =
                        ($amount *
                            $advantage->value) / 100;

                } else {

                    $discount =
                        $advantage->value;

                }

                $finalAmount =
                    $amount - $discount;

                break;

            case 'bon_reduction':

                $discount =
                    $advantage->value;

                $finalAmount =
                    $amount - $discount;

                break;

            case 'paiement_differe':

                $finalAmount =
                    ($amount *
                        $advantage->percentage_paid) / 100;

                break;

        }

        return [

            'initial_amount' => $amount,

            'discount' => $discount,

            'final_amount' => max(
                $finalAmount,
                0
            ),

            'remaining_amount' => max(
                $amount - $finalAmount,
                0
            ),

        ];
    }
}
