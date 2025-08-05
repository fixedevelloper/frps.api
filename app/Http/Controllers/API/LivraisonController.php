<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Livraison;
use Illuminate\Http\Request;

class LivraisonController extends Controller
{

    // Confirmer expédition
    public function marquerExpedie($id)
    {
        $livraison = Livraison::findOrFail($id);
        $livraison->statut = 'en_cours_livraison';
        $livraison->save();

        // Notifier le FOSA (ex : via event / notification)
        // Notification::send($livraison->commande->user, new LivraisonExpedieNotification($livraison));

        return response()->json(['message' => 'Statut mis à jour en "en cours de livraison"']);
    }

    // Confirmer réception
    public function confirmerReception(Request $request, $id)
    {
        $livraison = Livraison::findOrFail($id);

        $livraison->statut = 'livre';
        // Enregistrer note de satisfaction + conformité (optionnel)
        $livraison->save();

        return response()->json(['message' => 'Livraison confirmée reçue']);
    }

    // Signaler un problème
    public function signalerProbleme(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
        ]);

        $livraison = Livraison::findOrFail($id);
        $livraison->statut = 'en_investigation';
        $livraison->problème_description = $request->description;
        $livraison->save();

        // Gérer upload photos si nécessaire (stockage)

        // Notifier support FRPS

        return response()->json(['message' => 'Problème signalé et statut changé en "en investigation"']);
    }
}
