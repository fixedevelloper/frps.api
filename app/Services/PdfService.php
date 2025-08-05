<?php


namespace App\Services;


use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\File;

class PdfService
{
    protected $fpdf;

    public function __construct(Fpdf $fpdf)
    {
        $this->fpdf = $fpdf;
    }

    public function generateProformat($commande)
    {
        $this->fpdf->AddPage();
        $this->fpdf->Image(public_path('images/logo.png'), 10, 10, 40);
        $this->fpdf->SetFont('Arial', 'B', 16);
        $this->fpdf->Cell(0, 10, utf8_decode("FACTURE PRO FORMA"), 0, 1, 'C');
        $this->fpdf->Ln(10);

        $this->fpdf->SetFont('Arial', '', 12);
        $this->fpdf->Cell(0, 10, utf8_decode("Commande n°: " . $commande->id), 0, 1);
        $this->fpdf->Cell(0, 10, utf8_decode("Client : " . $commande->customer->name), 0, 1);
        $this->fpdf->Cell(0, 10, utf8_decode("Date : " . now()->format('d/m/Y')), 0, 1);
        $this->fpdf->Ln(10);

        // Exemple : tableau de produits de la commande
        $this->fpdf->SetFont('Arial', 'B', 12);
        $this->fpdf->Cell(80, 10, "Produit", 1);
        $this->fpdf->Cell(30, 10, "Qté", 1);
        $this->fpdf->Cell(40, 10, "PU", 1);
        $this->fpdf->Cell(40, 10, "Total", 1);
        $this->fpdf->Ln();

        $total = 0;
        $this->fpdf->SetFont('Arial', '', 12);
        foreach ($commande->products as $article) {
            $sousTotal = $article->quantite * $article->product->price;
            $total += $sousTotal;

            $this->fpdf->Cell(80, 10, utf8_decode($article->product->intitule), 1);
            $this->fpdf->Cell(30, 10, $article->quantite, 1);
            $this->fpdf->Cell(40, 10, number_format($article->product->price, 0, ',', ' ') . " FCFA", 1);
            $this->fpdf->Cell(40, 10, number_format($sousTotal, 0, ',', ' ') . " FCFA", 1);
            $this->fpdf->Ln();
        }

        $this->fpdf->SetFont('Arial', 'B', 12);
        $this->fpdf->Cell(150, 10, "TOTAL", 1);
        $this->fpdf->Cell(40, 10, number_format($total, 0, ',', ' ') . " FCFA", 1);
        $this->fpdf->Ln(20);

        // Création du répertoire
        $directory = public_path('proformas');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = "proforma_commande_{$commande->id}_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;

        $this->fpdf->Output('F', $fullPath);

        // Optionnel : mettre à jour la commande avec le lien du PDF
        $commande->update([
            'proforma_pdf' => 'proformas/' . $filename
        ]);
    }
    public function generateBordereau($commande)
    {
        $this->fpdf->AddPage();
        $this->fpdf->SetFont('Arial','B',14);
        $this->fpdf->Image(public_path('images/logo.png'), 10, 10, 40);

        $this->fpdf->Cell(0, 10, utf8_decode("BORDEREAU DE LIVRAISON"), 0, 1, 'C');
        $this->fpdf->Ln(10);

        // Infos commande
        $this->fpdf->SetFont('Arial', '', 12);
        $this->fpdf->Cell(0, 10, "Commande n°: " . $commande->id, 0, 1);
        $this->fpdf->Cell(0, 10, "Client : " . $commande->customer->name, 0, 1);
        $this->fpdf->Cell(0, 10, "Adresse : " . $commande->adresse_livraison, 0, 1);
        $this->fpdf->Cell(0, 10, "Date de livraison : " . now()->format('d/m/Y'), 0, 1);
        $this->fpdf->Ln(10);

        // Tableau des produits
        $this->fpdf->SetFont('Arial', 'B', 12);
        $this->fpdf->Cell(100, 10, "Produit", 1);
        $this->fpdf->Cell(30, 10, "Quantité", 1);
        $this->fpdf->Cell(60, 10, "Observation", 1);
        $this->fpdf->Ln();

        $this->fpdf->SetFont('Arial', '', 12);
        foreach ($commande->products as $article) {
            $this->fpdf->Cell(100, 10, utf8_decode($article->product->intitule), 1);
            $this->fpdf->Cell(30, 10, $article->quantite, 1);
            $this->fpdf->Cell(60, 10, '', 1); // Observation vide
            $this->fpdf->Ln();
        }

        $this->fpdf->Ln(15);

        // Signature
        $this->fpdf->Cell(0, 10, "Signature du livreur : ___________________", 0, 1);
        $this->fpdf->Cell(0, 10, "Signature du client : ____________________", 0, 1);
        $this->fpdf->Ln(20);
        // Création du dossier si nécessaire
        $directory = public_path('bordereaux');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = "bordereau_commande_{$commande->id}_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;
        $this->fpdf->Output('F', $fullPath);

        // Sauvegarde facultative du lien
        $relativePath = 'bordereaux/' . $filename;

        $commande->update([
          'bordereau_pdf' =>  $relativePath,
        ]);
    }
    public function generateFacture($commande, $avecTVA = false)
    {
        $this->fpdf->AddPage();
        $this->fpdf->SetFont('Arial','B',14);
        $this->fpdf->Image(public_path('images/logo.png'), 10, 10, 40);

        $this->fpdf->SetFont('Arial', 'B', 16);
        $this->fpdf->Ln(30); // saute après le logo
        $this->fpdf->Cell(0, 10, 'Facture définitive', 0, 1, 'C');

        $this->fpdf->SetFont('Arial','',12);

        // Informations client
        $this->fpdf->Cell(0,10,'Client : '.$commande->client->nom,0,1);
        $this->fpdf->Cell(0,10,'Date : '.now()->format('d/m/Y'),0,1);

        // Ligne d'entête
        $this->fpdf->SetFont('Arial','B',12);
        $this->fpdf->Cell(80,10,'Produit',1);
        $this->fpdf->Cell(30,10,'Quantité',1);
        $this->fpdf->Cell(40,10,'Prix Unitaire',1);
        $this->fpdf->Cell(40,10,'Total',1);
        $this->fpdf->Ln();

        $this->fpdf->SetFont('Arial','',12);
        $totalHT = 0;

        foreach ($commande->articles as $article) {
            $produit = $article->produit;
            $quantite = $article->quantite;
            $prix = $produit->prix;
            $total = $quantite * $prix;
            $totalHT += $total;

            $this->fpdf->Cell(80,10,$produit->nom,1);
            $this->fpdf->Cell(30,10,$quantite,1);
            $this->fpdf->Cell(40,10,number_format($prix, 0, ',', ' ').' FCFA',1);
            $this->fpdf->Cell(40,10,number_format($total, 0, ',', ' ').' FCFA',1);
            $this->fpdf->Ln();
        }

        // TVA 19.25%
        $totalHT = $commande->total;
        $tauxTVA = 0.1925; // TVA 19.25% par exemple
        $montantTVA = $avecTVA ? $totalHT * $tauxTVA : 0;
        $totalTTC = $totalHT + $montantTVA;

        $this->fpdf->Ln();
        $this->fpdf->Cell(150,10,'Total HT',1);
        $this->fpdf->Cell(40,10,number_format($totalHT, 0, ',', ' ').' FCFA',1);
        $this->fpdf->Ln();
        if ($avecTVA) {
            $this->fpdf->Cell(150,10,'TVA (19.25%)',1);
            $this->fpdf->Cell(40,10,number_format($montantTVA, 0, ',', ' ').' FCFA',1);
            $this->fpdf->Ln();
        }

        $this->fpdf->Cell(150,10,'Total TTC',1);
        $this->fpdf->Cell(40,10,number_format($totalTTC, 0, ',', ' ').' FCFA',1);

        // Mentions / signature
        $this->fpdf->SetFont('Arial', 'I', 10);
        $this->fpdf->MultiCell(0, 6, utf8_decode("Cette facture est payable à réception. Merci de votre confiance."), 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(0, 8, "Signature : __________________________", 0, 1);
        $this->fpdf->Ln(20);
        $this->fpdf->Ln(20);
        // Sauvegarde dans dossier "factures"
        $directory = public_path('factures');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = "facture_" . $commande->id . "_" . time() . ".pdf";
        $fullPath = $directory . '/' . $filename;
        $this->fpdf->Output('F', $fullPath);

        // Optionnel : sauvegarder le lien dans la base
        $commande->update([
            'facture_pdf' => 'factures/' . $filename
        ]);
    }

}
