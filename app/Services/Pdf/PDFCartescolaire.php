<?php


namespace App\Services\Pdf;


use App\Models\Setting;
use Carbon\Carbon;
use Codedge\Fpdf\Fpdf\Fpdf;

class PDFCartescolaire extends FPDF
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
    function Myheader($yerSchool){
        $setting=Setting::query()->first();
        $x1 = 10;
        $y1 = $this->GetY()+8;
        //Positionnement en bas
        $this->SetXY( $x1, $y1 );
        $this->SetFont('Times','B',12);
        $length = $this->w/2;
        $this->Cell($length,2,strtoupper(utf8_decode("Ministere de l'Éducation de base")),0,0,'C');
        $this->SetXY( $x1, $y1 + 5 );
        $this->SetFont('Times','',12);
       // $length = $this->GetStringWidth("DELEGATION REGIONALE DU LITTORAL" );
        $this->Cell($length,2,utf8_decode("DELEGATION REGIONALE DU LITTORAL"),0,0,'C');
        $this->SetXY( $x1, $this->GetY() + 5 );
      //  $length = $this->GetStringWidth("DELEGATION DEPARTEMENTALE DU WOURI" );
        $this->Cell($length,2,"DELEGATION DEPARTEMENTALE DU WOURI ",0,0,'C');
        $this->SetXY( $x1, $this->GetY() + 5 );
       // $length = $this->GetStringWidth("INSPECTION D'ARRONDISSEMENT DE DOUALA III" );
        $this->Cell($length,2,"INSPECTION D'ARRONDISSEMENT DE DOUALA III ",0,0,'C');
        $this->SetXY( $x1, $this->GetY() + 5 );
       // $length = $this->GetStringWidth(strtoupper(utf8_decode($setting->school_name)));
        $this->Cell($length,2,strtoupper(utf8_decode($setting->school_name)),0,0,'C');

        $x1=$this->w/2;
        $this->SetXY( $x1+10, 20 );
        $this->SetFont('Times','B',12);
        $this->Cell($this->GetX()-10,2,utf8_decode("REPUBLIQUE DU CAMEROUN"),0,'','C');
        $this->SetXY( $x1+10, $this->GetY() + 5 );
        $this->Cell($this->GetX()-10,2,utf8_decode("PAIX-TRAVAIL-PATRIE"),0,'','C');
        //$this->Line(10,$y1+25,$this->w-10,$y1+25);
    }
    function contentInformation($inscription){
        $setting=Setting::query()->first();
        $x1=10;
        $y1=$this->GetY();
        $this->SetXY( ($this->w/3), $y1+40 );
        $this->SetFont('Arial','B',12);
        $this->Cell(80,10,utf8_decode("CERTIFICAT DE SCOLARITÉ"),1,'','C');
        $this->SetXY( ($this->w/3), $this->GetY()+10 );
        $this->SetFont('Arial','',10);
        $this->Cell(80,10,utf8_decode("ANNÉE SCOLAIRE: ".$inscription->yearschool->name),0,'','C');
        $this->SetXY($x1+40, $this->GetY()+20 );
        $this->SetFont('Arial','',11);
        $this->Cell($this->w,10,utf8_decode("Je sousigné "),0,0,'');
        $this->SetXY($x1+10, $this->GetY()+10 );
        $this->Cell($this->w,10,utf8_decode("Directeur  ".$setting->school_name),0,1,'');
        $this->SetXY($x1+20, $this->GetY()+5 );
        $this->Cell(40,5,utf8_decode("Certifié que l' élève "),0,0,'');
        $this->MultiCell( $this->w,5,$inscription->student->user->first_name." ".$inscription->student->user->last_name);
        $this->SetXY($x1+10, $this->GetY()+5 );
        $length = $this->GetStringWidth(utf8_decode("Né(e) ".Carbon::parse($inscription->student->user->date_born)->format("d/m/Y")));
        $this->Cell($length+10,5,utf8_decode("Né(e) ".Carbon::parse($inscription->student->user->date_born)->format("d/m/Y")),0,0,'');
        $this->SetXY($length+40, $this->GetY() );
        $this->Cell($this->w-$length,5,utf8_decode("à ".$inscription->student->user->city),0,0,'');
        $this->SetXY($x1, $this->GetY()+5 );
        $this->Cell(20,5,"fils ou fille de ",0,0,'');
        $this->SetXY($x1+25, $this->GetY() );
        $this->Cell($this->w-($x1+25),5,$inscription->student->father_name.' et de '.$inscription->student->mother_name,0,0,'');
        $this->SetXY($x1, $this->GetY()+5 );
        $this->Cell(90,5,"est inscrit dans mon etablissement sous le matricule  ",0,0,'');
        $this->SetXY($x1+95, $this->GetY() );
        $this->Cell(55,5,utf8_decode($inscription->student->matricule),0,0,'');
        $this->SetXY($x1, $this->GetY()+5 );
        $this->Cell(50,5,"et en classe de   ",0,0,'');
        $this->SetXY($x1+50, $this->GetY() );
        $this->Cell(55,5,utf8_decode($inscription->salle->name),0,0,'');
        $this->SetXY($x1+40, $this->GetY()+15 );
        $this->Cell($this->w,5,utf8_decode("Cette piéce est délivrée pour servir et valoir ce qui est de droit. "),0,0,'');
        $this->SetXY($this->w/2, $this->GetY()+30 );
        $this->Cell(100,5,strtoupper("Le directeur "),0,0,'');
    }
    function bodyCertificat($inscription)
    {
        $setting=Setting::query()->first();
        //$this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);

        $this->SetXY(10, $this->GetY() + 25);
        $this ->SetFont('Times', 'B', 14);
        $this ->Cell(200, 10, utf8_decode("CERTIFICAT DE SCOLARITÉ"), 0, 0, 'C');
        $this ->Ln();
        $this->Cell(200, 10, utf8_decode("N°"), 0, 0, 'C');
        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', '', 14);
        $this->Cell(30, 10, utf8_decode("Je soussigné"), 0, 0, 'L');
        $this->Cell(100, 10, "_____________________________________________________________", 0, 0, 'L');
        $this->Ln();
        $this->SetX(10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("Directeur de ") . utf8_decode($setting->school_name), 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->Cell(80, 10, utf8_decode('Certifie que le (la) Nommé(e)'), 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(100, 2, utf8_decode($inscription->student->user->first_name." ".$inscription->student->user->last_name), 0, 0, 'L');
        $this->Ln();
        $this->SetX(80);
        $this->Cell(150, 8, "____________________________________________", 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->Cell(20, 10, utf8_decode('Né(e)'), 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(50, 10, Carbon::parse($inscription->student->user->date_born)->format("d/m/Y"), 0, 0, 'L');
        $this->SetFont('Times', '', 14);
        $this->Cell(10, 10, 'a', 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(100, 10, utf8_decode($inscription->student->user->city), 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->Cell(20, 10, "Matricule ", 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(100, 10, $inscription->student->matricule, 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->Cell(30, 10, "Fils ou Fille de ", 0, 0, 'L');
        $this->Cell(80, 8, "________________________________________________________", 0, 0, 'L');
        $this->Ln();
        $this->Cell(15, 10, 'et de', 0, 0, 'L');
        $this->Cell(100, 8, "_____________________________________________________________", 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->MultiCell(200, 8, utf8_decode('Est (a été) régulièrement inscrit(s) dans mon etablissement pour le compte de l\'année
scolaire '), 0);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(100, 10, $inscription->yearschool->name, 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->Cell(30, 10, "En classe de ", 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(100, 10, $inscription->salle->name, 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 14);
        $this->MultiCell(260, 10, utf8_decode("En foi de quoi se présent certificat de scolarite lui délivré pour servir et valoir ce que est
de droit. "), 0);

        $this->Ln();
        $this->SetX(80);
        $this->Cell(10, 6, utf8_decode("Fait à "), 0, 0, 'L');
        $this->Cell(30, 8, "______________________ ", 0, 0, 'L');
        $this->Cell(5, 6, " Le ", 0, 0, 'L');
        $this->Cell(20, 8, " ______________________", 0, 0, 'L');
        $this->Ln();
        $this->SetXY(100, $this->GetY() + 20);
        $this->Cell(100, 10, "LE DIRECTEUR ", 0, 0, 'R');
        $this->Ln();
    }

}
