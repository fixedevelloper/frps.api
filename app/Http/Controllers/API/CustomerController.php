<?php


namespace App\Http\Controllers\API;


use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Litige;
use App\Models\Paiement;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;

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
}
