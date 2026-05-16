<?php


namespace App\Http\Controllers;
use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        // Si tu passes des infos en query params
        $reference = $request->query('reference');

        return view('payment.success', compact('reference'));
    }

    public function failed(Request $request)
    {
        $reference = $request->query('reference');

        return view('payment.failed', compact('reference'));
    }
    public function pay(Request $request)
    {
        // On récupère la référence depuis l'URL (ex: /payment/pay?reference=CMD-123)
        $reference = $request->query('reference');

        // On cherche la commande en base de données
        $order = Paiement::where('reference', $reference)
            ->first();

        // Si la commande n'existe pas, on redirige avec une erreur
        if (!$order) {
            return abort(404, "Commande introuvable");
        }

        // On passe l'objet $order à la vue stylisée créée précédemment
        return view('payment.pay', compact('order'));
    }
}
