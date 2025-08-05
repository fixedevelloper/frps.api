<?php


namespace App\Services\Pdf;


use Codedge\Fpdf\Fpdf\Fpdf;

class PDFTimestable extends FPDF
{

    function bodyPage($horaires){

        $this->SetFont('Times', 'B', 14);
        $this->SetXY(10,$this->GetY()+5);
        $this->SetFillColor(113, 113, 113);
        $this->Cell(30, 8, 'Horaires', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Monday', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Thuesday', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Wednesday', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Thursday', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Friday', 1, 0, 'C', true);
        $this->Cell(42, 8, 'Saturday', 1, 0, 'C', true);
        $this->Ln();
        $this->SetFont('Times', '', 10);
       // $horaires=$data['horaires'];
        foreach ($horaires as $horaire =>$jours){
            $this->Cell(30, 8, $horaire, 1, 0, 'C');
            foreach ($jours as $jour =>$value){
                if ($value=='Pause'){
                    $this->Cell(42, 8, $value, 1, 0, 'C',true);
                }else{
                    $this->Cell(42, 8, $value, 1, 0, 'C');
                }
                //$this->Cell(40, 8, $value, 1, 0, 'C');
            }
            $this->Ln();
        }
    }
}
