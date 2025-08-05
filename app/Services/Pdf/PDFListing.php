<?php


namespace App\Services\Pdf;


use App\Models\Setting;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Carbon;

class PDFListing extends FPDF
{
    function Myheader()
    {
        $configuration = Setting::query()->first();
        $this->SetFont('Times', '', 10);
       // $logo=strstr($configuration->getLogo(), 'uploads/');
       // $path =$logo;
       // $this->Image($path, 90, 10,25,25);
        $this->SetXY(30, 10);
        $this->Cell(20, 6, utf8_decode($configuration->getHeaderLeft1()), 0, 0, 'C');
        $this->SetXY(30, 15);
        $this->Cell(20, 6, utf8_decode($configuration->getHeaderLeft2()), 0, 0, 'C');
        $this->SetXY(30, 20);
        $this->Cell(20, 6, utf8_decode($configuration->getHeaderLeft3()), 0, 0, 'C');
        $this->SetXY(30, 25);
        $this->Cell(10, 6, '', 0, 0, 'C');

        $this->SetXY(160, 10);
        $this->Cell(10, 6, utf8_decode($configuration->getHeaderRight1()), 0, 0, 'C');
        $this->SetXY(160, 15);
        $this->Cell(10, 6, utf8_decode($configuration->getHeaderRight2()), 0, 0, 'C');
        $this->SetXY(160, 20);
        $this->Cell(10, 6, utf8_decode($configuration->getHeaderLeft3()), 0, 0, 'C');
        $this->SetXY(160, 25);
        $this->Cell(10, 6, '', 0, 0, 'C');
        $this->SetFont('Times', 'B', 12);
        $this->SetY(35);
        $this->Cell(200, 6, strtoupper(utf8_decode($configuration->getName())), 0, 0, 'C');
        $this->SetY(40);
        $this->Cell(200, 6, strtoupper('Tel:' . $configuration->getPhone() . 'Bp: ' . $configuration->getBp()), 0, 0, 'C');
        $this->Ln(2);

    }
    function bodyListeStudent($rows)
    {
        $this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("LISTE DES ÉLÈVES"), 0, 0, 'C');
        $this->Ln();
        $this->SetFont('Times', 'B', 10);
        $this->Cell(10, 8, utf8_decode('N°'), 1, 0, 'C');
        $this->Cell(100, 8, utf8_decode('ÉLÈVE'), 1, 0, 'C');
        $this->Cell(30, 8, utf8_decode('MATRICULE'), 1, 0, 'C');
        $this->Cell(15, 8, utf8_decode('GENRE'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('DATE NAISSANCE'), 1, 0, 'C');
        $this->Ln();
        $i = 1;
        foreach ($rows as $row) {
            $this->SetFont('Times', 'B', 10);
            $this->Cell(10, 8, $i, 1, 0, 'L');
            $this->Cell(100, 8, utf8_decode(strtoupper($row->user->first_name." ".$row->user->last_name)), 1, 0, 'L');
            $this->Cell(30, 8, utf8_decode($row->matricule), 1, 0, 'L');
            $this->Cell(15, 8, utf8_decode($row->user->sexe), 1, 0, 'L');
            $this->Cell(35, 8, Carbon::parse($row->date_born)->format('d/m/Y'), 1, 0, 'L');
            $this->Ln();
            $i = $i + 1;
        }
    }
    function bodyListeProfessor($rows)
    {
        $this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("LISTE DES ENSEIGNANTS"), 0, 0, 'C');
        $this->Ln();
        $this->SetFont('Times', 'B', 10);
        $this->Cell(10, 8, utf8_decode('N°'), 1, 0, 'C');
        $this->Cell(100, 8, utf8_decode('NOM COMPLET'), 1, 0, 'C');
        $this->Cell(30, 8, utf8_decode('MATRICULE'), 1, 0, 'C');
        $this->Cell(15, 8, utf8_decode('GENRE'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('TÉLÉPHONE'), 1, 0, 'C');
        $this->Ln();
        $i = 1;
        foreach ($rows as $row) {
            $this->SetFont('Times', 'B', 10);
            $this->Cell(10, 8, $i, 1, 0, 'L');
            $this->Cell(100, 8, utf8_decode(strtoupper($row->getName())), 1, 0, 'L');
            $this->Cell(30, 8, utf8_decode($row->getMatricule()), 1, 0, 'L');
            $this->Cell(15, 8, utf8_decode($row->getSexe()), 1, 0, 'L');
            $this->Cell(35, 8, utf8_decode($row->getPhone()), 1, 0, 'L');
            $this->Ln();
            $i = $i + 1;
        }
    }
    function bodyListePersonnels($rows)
    {
        $this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("LISTE DU PERSONNELS"), 0, 0, 'C');
        $this->Ln();
        $this->SetFont('Times', 'B', 10);
        $this->Cell(10, 8, utf8_decode('N°'), 1, 0, 'C');
        $this->Cell(100, 8, utf8_decode('NOM COMPLET'), 1, 0, 'C');
        $this->Cell(30, 8, utf8_decode('MATRICULE'), 1, 0, 'C');
        $this->Cell(15, 8, utf8_decode('GENRE'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('TÉLÉPHONE'), 1, 0, 'C');
        $this->Ln();
        $i = 1;
        foreach ($rows as $row) {
            $this->SetFont('Times', 'B', 10);
            $this->Cell(10, 8, $i, 1, 0, 'L');
            $this->Cell(100, 8, utf8_decode(strtoupper($row->getName())), 1, 0, 'L');
            $this->Cell(30, 8, utf8_decode($row->getMatricule()), 1, 0, 'L');
            $this->Cell(15, 8, utf8_decode($row->getSexe()), 1, 0, 'L');
            $this->Cell(35, 8, utf8_decode($row->getPhone()), 1, 0, 'L');
            $this->Ln();
            $i = $i + 1;
        }
    }
    function bodyListeStudentregister($rows)
    {
        $this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);

        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("LISTE DES ÉLÈVES INSCRITS"), 0, 0, 'C');
        $this->Ln();
        $this->SetFont('Times', 'B', 10);
        $this->Cell(10, 8, utf8_decode('N°'), 1, 0, 'C');
        $this->Cell(85, 8, utf8_decode('ÉLÈVE'), 1, 0, 'C');
        $this->Cell(28, 8, utf8_decode('MATRICULE'), 1, 0, 'C');
        $this->Cell(15, 8, utf8_decode('GENRE'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('DATE NAISSANCE'), 1, 0, 'C');
        $this->Cell(17, 8, utf8_decode('CLASSE'), 1, 0, 'C');
        $this->Ln();
        $i = 1;
        foreach ($rows as $row) {
            $this->SetFont('Times', 'B', 10);
            $this->Cell(10, 8, $i, 1, 0, 'L');
            $this->Cell(85, 8, utf8_decode(strtoupper($row->student->user->first_name." ".$row->student->user->last_name)), 1, 0, 'L');
            $this->Cell(28, 8, utf8_decode($row->student->matricule), 1, 0, 'L');
            $this->Cell(15, 8, utf8_decode($row->student->user->sexe), 1, 0, 'L');
            $this->Cell(35, 8, Carbon::parse($row->student->date_born)->format('d/m/Y'), 1, 0, 'L');
            $this->Cell(17, 8, utf8_decode($row->salle->name), 1, 0, 'L');
            $this->Ln();
            $i = $i + 1;
        }
    }

    function bodyListeStudentregisterclasse($rows, $classe)
    {
        $this->Line(12, $this->GetY() + 5, 200, $this->GetY() + 5);
        $this->SetXY(10, $this->GetY() + 10);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, utf8_decode("LISTE DES ÉLÈVES INSCRITS EN CLASSE DE " . $classe->name), 0, 0, 'C');
        $this->Ln();
        $this->SetFont('Times', 'B', 10);
        $this->Cell(10, 8, utf8_decode('N°'), 1, 0, 'C');
        $this->Cell(85, 8, utf8_decode('ÉLÈVE'), 1, 0, 'C');
        $this->Cell(28, 8, utf8_decode('MATRICULE'), 1, 0, 'C');
        $this->Cell(15, 8, utf8_decode('GENRE'), 1, 0, 'C');
        $this->Cell(35, 8, utf8_decode('DATE NAISSANCE'), 1, 0, 'C');
        $this->Cell(17, 8, utf8_decode('CLASSE'), 1, 0, 'C');
        $this->Ln();
        $i = 1;
        foreach ($rows as $row) {
            $this->SetFont('Times', 'B', 10);
            $this->Cell(10, 8, $i, 1, 0, 'L');
            $this->Cell(85, 8, utf8_decode(strtoupper($row->student->user->first_name." ".$row->student->user->last_name)), 1, 0, 'L');
            $this->Cell(28, 8, utf8_decode($row->student->matricule), 1, 0, 'L');
            $this->Cell(15, 8, utf8_decode($row->student->user->sexe), 1, 0, 'L');
            $this->Cell(35, 8, Carbon::parse($row->student->date_born)->format('d/m/Y'), 1, 0, 'L');
            $this->Cell(17, 8, utf8_decode($row->salle->name), 1, 0, 'L');
            $this->Ln();
            $i = $i + 1;
        }
    }

}
