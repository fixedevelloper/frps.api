<?php


namespace App\Services\Pdf;


use App\Models\Commande;
use Carbon\Carbon;
use Codedge\Fpdf\Fpdf\Fpdf;

class PDFRecu extends FPDF
{
// variables privées
    var $colonnes;
    var $format;
    var $angle=0;


// fonctions privées
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    function Rotate($angle, $x=-1, $y=-1)
    {
        if($x==-1)
            $x=$this->x;
        if($y==-1)
            $y=$this->y;
        if($this->angle!=0)
            $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
        }
    }

    function _endpage()
    {
        if($this->angle!=0)
        {
            $this->angle=0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
    function filgramme( $texte )
    {
        $this->SetFont('Arial','B',50);
        $this->SetTextColor(203,203,203);
        $this->Rotate(30,55,100);
        $this->Text(55,100,utf8_decode($texte));
        $this->Rotate(0);
        $this->SetTextColor(0,0,0);
    }
    public function generateProformat(Commande $commande)
    {
        $this->AddPage();
        $this->SetFont('Arial', 'B', 16);

        // En-tête
        $this->Cell(0, 10, utf8_decode("FACTURE PRO FORMA"), 0, 1, 'C');
        $this->Ln(10);

        // Infos client
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, utf8_decode("Client : M. Jean Dupont"), 0, 1);
        $this->Cell(0, 10, utf8_decode("Date : " . date('d/m/Y')), 0, 1);
        $this->Ln(10);

        // Tableau des articles
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(80, 10, utf8_decode("Désignation"), 1);
        $this->Cell(30, 10, "Quantité", 1);
        $this->Cell(40, 10, "Prix Unitaire", 1);
        $this->Cell(40, 10, "Total", 1);
        $this->Ln();

        // Exemple de données
        $articles = [
            ['nom' => 'Produit A', 'qte' => 2, 'pu' => 1500],
            ['nom' => 'Produit B', 'qte' => 1, 'pu' => 3000],
        ];

        $total = 0;
        $this->SetFont('Arial', '', 12);
        foreach ($articles as $article) {
            $ligne = $article['qte'] * $article['pu'];
            $total += $ligne;

            $this->Cell(80, 10, utf8_decode($article['nom']), 1);
            $this->Cell(30, 10, $article['qte'], 1);
            $this->Cell(40, 10, number_format($article['pu'], 0, ',', ' ') . " FCFA", 1);
            $this->Cell(40, 10, number_format($ligne, 0, ',', ' ') . " FCFA", 1);
            $this->Ln();
        }

        // Total
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(150, 10, "TOTAL", 1);
        $this->Cell(40, 10, number_format($total, 0, ',', ' ') . " FCFA", 1);
        $this->Ln(20);

        // Footer
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, utf8_decode("Merci pour votre confiance."), 0, 1, 'C');
        $directory = public_path('proformas');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true); // Crée le dossier avec permissions récursives
        }
        $filename = 'proformas/proforma_' . $commande->id . '.pdf';
        $fullPath = public_path($filename);
        $this->Output('F', $fullPath);

    }

    public function generateBorderauLivraison(Commande $commande)
    {

    }
}
