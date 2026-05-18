<?php


namespace App\Http\Exports;

use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvExporter
{
    /**
     * Génère un flux de téléchargement CSV pour les produits
     */
    public function download(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="export_produits_' . now()->format('d_m_Y') . '.csv"',
        ];

        // On retourne la réponse streamée
        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Ajout du BOM UTF-8 pour Excel (gestion des accents)
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes des colonnes
            fputcsv($handle, ['ID', 'Référence', 'Désignation', 'Prix (FCFA)', 'Stock Total', 'Financement','Lot','Date Perem']);

            // Traitement par paquets (chunk) pour économiser la mémoire du serveur
            Product::with('stocks')
                ->chunk(200, function ($products) use ($handle) {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->id,
                            $product->reference,
                            $product->intitule,
                            $product->price,
                            $product->stocks->sum('quantite_actuelle'), // Cumul des lots FIFO/LIFO
                            $product->financement ?? 'N/A',
                            $product->lot_prioritaire->num_lot ?? 'N/A',
                            $product->lot_prioritaire->date_peremption ?? 'N/A'
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
