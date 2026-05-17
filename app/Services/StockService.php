<?php


namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockService
{
    /**
     * Fait sortir une quantité de produit selon la stratégie choisie et enregistre les mouvements.
     *
     * @param mixed $product
     * @param int $quantiteDemandee
     * @param string $strategie
     * @param string|null $motif Optionnel : Permet de spécifier la raison (ex: "Vente Commande #102")
     * @return bool
     * @throws Exception
     */
    public function sortirStock($product, int $quantiteDemandee, string $strategie = 'FIFO', ?string $motif = null)
    {
        $productId = $product->id;

        // 1. Calculer le stock total disponible tous lots confondus
        $stockDisponible = Stock::where('product_id', $productId)->disponibles()->sum('quantite_actuelle');

        if ($stockDisponible < $quantiteDemandee) {
            throw new Exception("Stock insuffisant pour ce produit (Product: $product->intitule | Disponible: $stockDisponible).");
        }

        // 2. Récupérer les lots triés selon la stratégie choisie
        $query = Stock::where('product_id', $productId)->disponibles();

        if ($strategie === 'FIFO') {
            $lots = $query->fifo()->get();
        } elseif ($strategie === 'LIFO') {
            $lots = $query->lifo()->get();
        } else {
            // FEFO par défaut (First Expired, First Out)
            $lots = $query->fefo()->get();
        }

        $resteASortir = $quantiteDemandee;
        $motifFinal = $motif ?? "Sortie de stock automatique ({$strategie})";

        // 3. Décrémentation par lot et enregistrement des mouvements via une Transaction
        DB::transaction(function () use ($lots, &$resteASortir, $motifFinal) {
            foreach ($lots as $lot) {
                if ($resteASortir <= 0) break;

                // Déterminer la quantité exacte à prélever sur CE lot spécifique
                $quantiteAPrelever = min($lot->quantite_actuelle, $resteASortir);

                // Mettre à jour le stock du lot
                $lot->quantite_actuelle -= $quantiteAPrelever;
                $lot->save();

                // Créer le mouvement de stock lié à ce lot spécifique
                StockMovement::create([
                    'product_id' => $lot->product_id,
                    'stock_id' => $lot->id,
                    'type' => 'SORTIE',
                    'quantite' => $quantiteAPrelever,
                    'motif' => $motifFinal,
                    'user_id' => Auth::id(), // ID de l'utilisateur connecté (sera null si exécuté via une commande console/cron)
                ]);

                // Déduire ce qui a été pris du reste global à sortir
                $resteASortir -= $quantiteAPrelever;
            }
        });

        return true;
    }
}
