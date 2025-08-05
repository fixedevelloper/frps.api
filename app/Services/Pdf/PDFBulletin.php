<?php


namespace App\Services\Pdf;


use App\Helpers\QRCode;

use App\Models\Evaluation;

use App\Models\EvaluationNote;
use App\Models\EvaluationTimestable;
use App\Models\Inscription;
use App\Models\ProfessorClasse;
use App\Models\ReportCard;
use App\Models\ReportCardEvaluation;
use App\Models\Setting;
use App\Models\SubjetClasse;
use Codedge\Fpdf\Fpdf\Fpdf;

class PDFBulletin extends FPDF
{

    var $colonnes;
    var $format;
    var $angle = 0;


// fonctions privées
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));

        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }

    function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    function filgramme($texte)
    {
        $this->SetFont('Arial', 'B', 50);
        $this->SetTextColor(203, 203, 203);
        $this->Rotate(30, 55, 100);
        $this->Text(55, 100, utf8_decode($texte));
        $this->Rotate(0);
        $this->SetTextColor(0, 0, 0);
    }

    function Myheader()
    {
        $setting = Setting::query()->first();
        $x1 = 10;
        $y1 = $this->GetY() + 8;
        //Positionnement en bas
        $this->SetXY($x1, $y1);
        $this->SetFont('Arial', 'B', 12);
        $length = $this->GetStringWidth($setting->school_name);
        $this->Cell($length, 2, utf8_decode($setting->school_name));
        $this->SetXY($x1, $y1 + 5);
        $this->SetFont('Arial', '', 10);
        $length = $this->GetStringWidth($setting->address);
        $this->Cell($length, 2, utf8_decode($setting->address));
        $this->SetXY($x1, $y1 + 10);
        $this->SetFont('Arial', '', 10);
        $length = $this->GetStringWidth("BP " . $setting->school_name);
        $this->Cell($length, 2, "BP " . $setting->bp);
        $x1 = $length + 100;

        $this->SetLineWidth(0.1);
        $this->SetFillColor(192);
        $this->RoundedRect($x1, $y1, 50, 15, 2.5, 'DF');
        $this->SetXY($x1 + 4, $y1 + 8);
        $this->Cell($length, 2, strtoupper(utf8_decode("Reçu de paiement")));
        $this->SetXY($x1, $y1 + 20);
        $this->Cell(20, 2, utf8_decode("Année scolaire: "));
        $this->Line(10, $y1 + 25, $this->w - 10, $y1 + 25);
    }

    function informationStudent($inscription)
    {
        $y1 = $this->GetY();
        $y2 = $this->GetY() + 15;
        $x1 = 10;
        $this->SetXY($x1, $y1 + 10);
        $length = $this->GetStringWidth("Nom et prenoms de l' élève");
        $this->Cell(45, 2, utf8_decode("Nom et prenoms de l' élève"));
        $this->Cell($x1 + 200, 2, ": " . utf8_decode($inscription->student->user->first_name) . " " . utf8_decode($inscription->student->user->last_name));
        $this->SetXY($x1, $this->GetY() + 10);
        $this->Cell(35, 2, utf8_decode("Matricule de l' élève "));
        $this->Cell(30, 2, ": " . utf8_decode($inscription->student->matricule));
        $this->SetXY($x1, $this->GetY() + 5);
        $this->Cell(35, 2, utf8_decode("Date naissance "));
        $this->Cell(30, 2, ": " . utf8_decode($inscription->student->user->date_born));
        $this->SetXY($x1, $this->GetY() + 5);
        $this->Cell(35, 2, utf8_decode("Situation "));
        $this->Cell(30, 2, ": " . utf8_decode($inscription->student->regime));
        $x2 = $this->GetX() + 2;

        $this->SetXY($x2, $y2 + 5);
        $this->Cell(20, 2, utf8_decode("Titulaire"));
        $this->Cell(40 + 2, 2, ": " . utf8_decode($inscription->student->matricule));
        $this->SetXY($x2, $this->GetY() + 5);
        $this->Cell(20, 2, utf8_decode("Lieu"));
        $this->Cell(40 + 2, 2, ": " . utf8_decode($inscription->student->matricule));
        $this->SetXY($x2, $this->GetY() + 5);
        $this->Cell(20, 2, utf8_decode("Classe"));
        $this->Cell(40 + 2, 2, ": " . utf8_decode($inscription->salle->name));
        /*        $this->SetXY( $x2, $this->GetY()+5 );
                $this->Cell(20,2,utf8_decode("Redoublant"));
                $this->Cell(40+2,2,": ".utf8_decode($inscription->salle->name));*/

    }


    function headerTable($bulletin)
    {
        $listExams = Evaluation::query()->where('trimestre_id', $bulletin->trimestre->id)->get();
        $this->SetFont('Times', 'B', 9);
        $this->SetXY(10,$this->GetY()+5);
        $this->SetFillColor(113, 113, 113);
        $this->Cell(45, 8, 'Disciplines', 1, 0, 'C', true);
        foreach ($listExams as $item) {
            $this->Cell(8, 8, 'T1', 1, 0, 'C', true);
        }
        $this->Cell(8, 8, 'Avr', 1, 0, 'C', true);
        $this->Cell(8, 8, 'Coef', 1, 0, 'C', true);

        $this->Cell(10, 8, 'NXC', 1, 0, 'C', true);
        $this->Cell(10, 8, 'Rang', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Mention', 1, 0, 'C', true);
        $this->SetFont('Times', 'B', 8);
        $this->Cell(10, 8, 'Min N', 1, 0, 'C', true);
        $this->Cell(10, 8, 'Max N', 1, 0, 'C', true);
        $this->SetFont('Times', 'B', 9);
        $this->Cell(15, 8, 'M.Classe', 1, 0, 'C', true);
        $this->Cell(45, 8, 'Enseignant', 1, 0, 'C', true);
        $this->Ln();
    }

    function headerName($bulletin)
    {
        $inscription = $bulletin->student;
        $this->SetY(45);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(200, 10, 'BULLETIN DE NOTES ' . strtoupper($bulletin->trimestre->name), 0, 0, 'C');
        $this->Ln(1);
        $this->Line(0, 55, 256, 55);
        $this->SetX(5);
        $this->SetFont('Times', '', 14);
        $this->SetY(59);
        $this->Cell(20, 6, 'Name: ', 0, 0, 'L');
        $this->SetFont('Times', 'B', 14);
        $this->Cell(150, 6, utf8_decode(strtoupper($bulletin->inscription->student->user->first_name. ' '.$bulletin->inscription->student->user->last_name)), 0, 0, 'L');
        $this->Ln();
        $this->SetFont('Times', '', 12);
        $this->Sety(64);
        $this->Cell(60, 6, 'Matricule: ' . $bulletin->inscription->student->matricule, 0, 0, 'L');
        $this->Cell(60, 6, 'Titulaire: ' . '', 0, 0, 'L');
        $this->Ln();
        $this->Sety(68);
        $this->Cell(60, 6, 'Date naissance: ' . $bulletin->inscription->student->user->dateborn, 0, 0, 'L');
        $this->Cell(60, 6, 'Lieu: ' . utf8_decode($bulletin->inscription->student->getLieunaissance), 0, 0, 'L');
        $file = 'logo.png';
        $textqr = "Moy:" . $bulletin->moyenne . " Rang:" . $bulletin->rank . " Matricule:" . $bulletin->inscription->student->matricule;
        //header("Content-type: image/png");
        $qr = QRCode::getMinimumQRCode($textqr, QR_ERROR_CORRECT_LEVEL_L);
        $im = $qr->createImage(2, 2);
        $valimage = imagepng($im, "qr-" . $bulletin->inscription->student->matricule . '.png');

        if ($valimage) {
            $this->Image("qr-" . $bulletin->inscription->student->matricule . '.png', 150, 57);
        }

        $img = is_null($bulletin->inscription->student->user->photo) ? "" : $bulletin->inscription->student->user->photo;
        if (is_file($img)) {
            $this->RoundedRect(180, 56, 23, 23, 1.5);
            $this->Image($img, 181, 57, 20, 20);
        } else {
            $this->RoundedRect(180, 56, 23, 23, 1.5);
        }
        $this->Ln();
        $this->Sety(73);
        $this->Cell(60, 4, 'Situation: ' . $bulletin->inscription->student->regime, 0, 0, 'L');
        $this->Cell(60, 4, 'Classe: ' . $bulletin->inscription->salle->name, 0, 0, 'L');
        $this->Ln();
        $this->Cell(60, 4, 'Gender: ' . $bulletin->inscription->student->sexe, 0, 0, 'L');
        $this->Cell(60, 4, 'Effectif: ' . '', 0, 0, 'L');
        $this->Ln();
        $this->SetXY(10,80);
    }

    function bodyTable($bulletin,$groups)
    {
        $inscription = Inscription::query()->find($bulletin->inscription_id);
        $listExams = Evaluation::query()->where('trimestre_id', $bulletin->trimestre->id)->get();
        $this->SetY($this->GetY() + 1);
        $h = 88;
        $siwe=1;


        foreach ($groups as $group) {
            $subject_classes = SubjetClasse::query()->where(['classe_id' => $inscription->salle->classe_id, 'subject_group_id' => $group->subject_group_id])->get();
            /*foreach ($subject_classes as $subject_class) {
                $note_som = 0.0;
                $coef = 0.0;
                $note_coef_som = 0.0;
                $examen_tables = collect();
                foreach ($listExams as $exam_item) {
                    $tables = EvaluationTimestable::query()
                        ->leftJoin('evaluation_notes', 'evaluation_notes.evaluation_timestable_id', '=', 'evaluation_timestables.id')
                        ->leftJoin('subjet_classes', 'subjet_classes.id', '=', 'evaluation_timestables.subjet_classe_id')
                        ->leftJoin('subjects', 'subjects.id', '=', 'subjet_classes.subject_id')
                        ->where([
                            'evaluation_timestables.evaluation_id' => $exam_item->id,
                            'subject_group_id' => $subject_class->subject_group_id,
                            'inscription_id' => $inscription->id,
                            'subjet_classes.id' => $subject_class->id // <-- correction ici
                        ])
                        ->get([
                            'mark',
                            'inscription_id',
                            'subject_group_id',
                            'subjet_classe_id',
                            'subjects.name',
                            'coefficient',
                            'evaluation_timestables.evaluation_id'
                        ]);
                    $examen_tables = $examen_tables->merge($tables);
                }
                logger($examen_tables);
                $siwe= sizeof($examen_tables);
                foreach ($examen_tables as $item){
                   if (!empty($item)){
                       $table=$item;
                       $note_coef_som += $table['mark'] * $table['coefficient'];
                       $note_som += $table['mark'];
                       $coef += $table['coefficient'];
                   }


                }
                $professor_classes=ProfessorClasse::query()->where(['subjet_classe_id'=>$subject_class->id,
                    'salle_classe_id'=>$inscription->salle_id])->get();
                $teacher='';
              foreach ($professor_classes as $professor_classe){
                    $teacher .= '-'.$professor_classe->professor->user->first_name;
                }
                $this->SetFont('Times', '', 8);
                $this->Cell(45, 6, utf8_decode($subject_class->subject->name), 1, 0, 'L');

                foreach ($examen_tables as $table) {
                  //  logger($table);
                    $this->Cell(8, 6,empty($table[0])? '': $table[0]['mark'], 1, 0, 'C');
                }
                $this->Cell(8, 6, $coef==0?'':$note_coef_som / $coef, 1, 0, 'C');
                $this->Cell(8, 6, $subject_class->coefficient, 1, 0, 'C');
                $this->Cell(10, 6, $note_coef_som / sizeof($examen_tables), 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(15, 6, 'Passable', 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(15, 6, '', 1, 0, 'C');
                $this->Cell(45, 6,strtoupper($teacher) , 1, 0, 'L');
                $this->Ln();
                // }
            }*/
            foreach ($subject_classes as $subject_class) {
                $note_som = 0.0;
                $coef = 0.0;
                $note_coef_som = 0.0;

                // On initialise une seule collection fusionnée
                $examen_tables = collect();

                foreach ($listExams as $exam_item) {
                    $tables = EvaluationTimestable::query()
                        ->leftJoin('evaluation_notes', 'evaluation_notes.evaluation_timestable_id', '=', 'evaluation_timestables.id')
                        ->leftJoin('subjet_classes', 'subjet_classes.id', '=', 'evaluation_timestables.subjet_classe_id')
                        ->leftJoin('subjects', 'subjects.id', '=', 'subjet_classes.subject_id')
                        ->where([
                            'evaluation_timestables.evaluation_id' => $exam_item->id,
                            'subject_group_id' => $subject_class->subject_group_id,
                            'inscription_id' => $inscription->id,
                            'subjet_classes.id' => $subject_class->id
                        ])
                        ->get([
                            'mark',
                            'rank',
                            'inscription_id',
                            'subject_group_id',
                            'subjet_classe_id',
                            'subjects.name',
                            'coefficient',
                            'evaluation_timestables.evaluation_id'
                        ]);

                    $examen_tables = $examen_tables->merge($tables);
                }

                foreach ($examen_tables as $table) {
                    if (!empty($table->mark)) {
                        $note_coef_som += $table->mark * $table->coefficient;
                        $note_som += $table->mark;
                        $coef += $table->coefficient;
                    }
                }

                $professor_classes = ProfessorClasse::query()
                    ->where([
                        'subjet_classe_id' => $subject_class->id,
                        'salle_classe_id' => $inscription->salle_id
                    ])
                    ->get();

                $teacher = '';
                foreach ($professor_classes as $professor_classe) {
                    $teacher .= '-' . $professor_classe->professor->user->first_name;
                }

                // Affichage PDF
                $this->SetFont('Times', '', 8);
                $this->Cell(45, 6, utf8_decode($subject_class->subject->name), 1, 0, 'L');

                foreach ($listExams as $exam_item) {
                    // On récupère la note pour cet exam uniquement
                    $note = $examen_tables->firstWhere('evaluation_id', $exam_item->id);
                    $this->Cell(8, 6, $note ? $note->mark : '', 1, 0, 'C');
                }

                $this->Cell(8, 6, $coef == 0 ? '' : number_format($note_coef_som / $coef, 2), 1, 0, 'C');
                $this->Cell(8, 6, $subject_class->coefficient, 1, 0, 'C');
                $this->Cell(10, 6, $examen_tables->count() == 0 ? '' : number_format($note_coef_som / $examen_tables->count(), 2), 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(15, 6, 'Passable', 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(10, 6, '', 1, 0, 'C');
                $this->Cell(15, 6, '', 1, 0, 'C');
                $this->Cell(45, 6, strtoupper($teacher), 1, 0, 'L');
                $this->Ln();
            }

            $this->SetFont('Times', '', 8);

            $this->SetY($this->GetY());
            $this->SetFillColor(211, 211, 211);
            $this->SetFont('Times', 'B', 8);
            $this->Cell(45+8+(8*$siwe), 6, utf8_decode($group->subject_group->name), 1, 0, 'L', true);
            $this->SetFont('Times', '', 8);
            $this->Cell(8, 6, '', 1, 0, 'C', true);
            $this->Cell(8, 6, $group->total_coef, 1, 0, 'C', true);
            $this->Cell(10, 6, $group->total_note, 1, 0, 'C', true);
            $this->Cell(10, 6, $group->rank, 1, 0, 'C', true);
            $this->Cell(15, 6, 'p', 1, 0, 'C', true);
            $this->Cell(10, 6, '', 1, 0, 'C', true);
            $this->Cell(10, 6, '', 1, 0, 'C', true);
            $this->Cell(15, 6, '10', 1, 0, 'C', true);
            $this->Cell(45, 6, 'AVR:' . round($group->average,2), 1, 0, 'C', true);
            $this->Ln();
            $h += (6 * sizeof($subject_classes) + 6);
            //$this->SetY($h);
        }
    }
    function footerTable($bulletin,$listExams){
        $wt=53+sizeof($listExams)*8;
        $this->SetXY(5,$this->GetY());
        $this->SetY($this->GetY());
        $this->SetFont('Times', 'B', 10);
        $this->Cell($wt, 8, 'Total', 1, 0, 'C');
        $this->Cell(8, 8, $bulletin->total_coef, 1, 0, 'C');
        $this->Cell(10, 8, $bulletin->total_mark, 1, 0, 'C');
        $this->Cell(10, 8, '', 1, 0, 'C');
        $this->SetFillColor(113, 113, 113);
        $this->Cell(48, 8, 'Average', 1, 0, 'C',true);
        $this->Cell(47, 8, 'Rank', 1, 0, 'C',true);
        $this->Ln();
        $this->SetX(10+$wt+28);
        $this->Cell(48, 8, $bulletin->average, 1, 0, 'C');
        $this->Cell(47, 8, $bulletin->rank, 1, 0, 'C');
        $this->Ln();
        /*******************************************/
        $size=$this->w-10;
        $wfooter=($size/3)-4;
        $y1=$this->GetY();
        $y2=$this->GetY();
        $y3=$this->GetY()+2;
        $this->SetY($this->GetY()+2);
        $this->Cell(($size/3)-4, 8, 'Discipline', 1, 0, 'C');
        $y1+=10;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, 'Abs J', 1, 0, 'L');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $y1+=7;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, 'Abs NJ', 1, 0, 'L');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $y1+=7;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, 'Exclu', 1, 0, 'L');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $y1+=7;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, 'Blames', 1, 0, 'L');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $y1+=7;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $y1+=7;
        $this->SetXY(10,$y1);
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $this->Cell(($wfooter/2), 7, '', 1, 0, 'C');
        $reportCards=ReportCard::query()
            ->leftJoin('inscriptions', 'inscriptions.id', '=', 'report_cards.inscription_id')
            ->where('inscriptions.salle_id', $bulletin->inscription->salle_id)
            ->where('trimestre_id', $bulletin->trimestre_id)->orderBy('average', 'desc')->get();
        $this->SetXY(($wfooter)+12,$y2+2);
        $this->Cell(($size/3)-4,8, 'Notes', 1, 0, 'C');
        $y2+=10;
        $this->SetXY(($wfooter)+12,$y2);
        $this->Cell(($wfooter/2)+15, 7, 'Class general average', 1, 0, 'L');
        $this->Cell(($wfooter/3)-4.6, 7, $bulletin->average_class, 1, 0, 'L');
        $y2+=7;
        $this->SetXY(($wfooter)+12,$y2);
        $first = $reportCards->first(); // Meilleur élève
        $last = $reportCards->last();   // Moins bon élève
        $this->Cell(($wfooter/2)+15, 7, 'Avr of first', 1, 0, 'L');
        $this->Cell(($wfooter/3)-4.6, 7,  number_format($first->average,2), 1, 0, 'L');
        $y2+=7;
        $this->SetXY(($wfooter)+12,$y2);


        $this->Cell(($wfooter/2)+15, 7, 'Avr of last', 1, 0, 'L');
        $this->Cell(($wfooter/3)-4.6, 7, number_format($last->average,2), 1, 0, 'L');
        $y2+=7;
        $this->SetXY(($wfooter)+12,$y2);
        $passing = $reportCards->filter(function ($item) {
            return $item->average >= 10;
        });
        $this->Cell(($wfooter/2)+15, 7, 'Avgr>=10', 1, 0, 'L');
        $this->Cell(($wfooter/3)-4.6, 7, count($passing), 1, 0, 'C');
        $y2+=7;
        $this->SetXY(($wfooter)+12,$y2);

        $this->Cell(($wfooter/2)+15, 7, 'Succes rate(%)', 1, 0, 'L');
        $this->Cell(($wfooter/3)-4.6, 7, (count($passing)/count($reportCards))*100, 1, 0, 'C');
        /************************************/
       // $this->SetX($this->GetX()+2);
        $this->SetXY(($wfooter*2)+14,$y3);
        $this->Cell($wfooter, 8, 'Work student', 1, 0, 'C');
        $this->SetXY(($wfooter*2)+14,$this->GetY()+8);
        $this->Cell($wfooter/2, 7, 'Period', 1, 0, 'C');
        $this->Cell($wfooter/4, 7, 'AVR', 1, 0, 'C');
        $this->Cell($wfooter/4, 7, 'RANK', 1, 0, 'C');
        foreach ($listExams as $listExam){
            $report_evaluation=ReportCardEvaluation::query()->firstWhere(['inscription_id'=>$bulletin->inscription_id,
                'evaluation_id' => $listExam->id]);
            $this->SetXY(($wfooter*2)+14,$this->GetY()+7);
            $this->Cell($wfooter/2, 7, $report_evaluation->evaluation->name, 1, 0, 'C');
            $this->Cell($wfooter/4, 7, number_format($report_evaluation->average,2), 1, 0, 'C');
            $this->Cell($wfooter/4, 7, $report_evaluation->rank, 1, 0, 'C');
        }
        $this->Ln();
        $this->SetXY(10,$y1+10);
        $this->Cell(($this->w/3)-6, 6, 'Observations parents', 1, 0, 'C');
        $this->Cell(($this->w/3)-6, 6, 'Visa titulaire', 1, 0, 'C');
        $this->Cell(($this->w/3)-6, 6, 'Visa Chef etablissement', 1, 0, 'C');
        $this->Ln();
        $this->SetX(10);
        $this->Cell(($this->w/3)-6, 15, '', 1, 0, 'C');
        $this->Cell(($this->w/3)-6, 15, '', 1, 0, 'C');
        $this->Cell(($this->w/3)-6, 15, '', 1, 0, 'C');
        /*  $this->SetX(5);
          $this->Cell(100, 8, 'Decision du conseil de classe', 1, 0, 'C');
          $this->Cell(100, 8, 'Notes', 1, 0, 'C');
          $this->Ln();
       /*
          $this->SetX(5);
          $this->Cell(50, 8, 'Discipline', 1, 0, 'C');
          $this->Cell(50, 8, 'Travail', 1, 0, 'C');
          $this->Cell(50, 8, 'Rappel note', 1, 0, 'C');
          $this->Cell(50, 8, 'Classe', 1, 0, 'C');
          $this->Ln();
          $this->SetFont('Times', '', 8);
          $this->SetX(5);
          $this->Cell(25, 8, 'Absence J', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Tableau H', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, utf8_decode('Moy Evaluation n°1'), 1, 0, 'C');
          $this->Cell(25, 8, 10, 1, 0, 'C');
          $this->Cell(25, 8, utf8_decode('Moy trimestre n°').$bulletin->id, 1, 0, 'C');
          $this->Cell(25, 8, 10, 1, 0, 'C');

          $this->Ln();
          $this->SetX(5);
          $this->Cell(25, 8, 'Absence NJ', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Mention ', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, utf8_decode('Moy Evaluation n°2'), 1, 0, 'C');
          $this->Cell(25, 8, 10, 1, 0, 'C');
          $this->Cell(25, 8, 'Rang', 1, 0, 'C');
          $this->Cell(25, 8, $bulletin->rank, 1, 0, 'C');

          $this->Ln();
          $this->SetX(5);
          $this->Cell(25, 8, 'Avertissement', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Avertis conduite', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne trimestre 1', 1, 0, 'C');
          $this->Cell(25, 8, '$moyTrim1', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne premier', 1, 0, 'C');
          $this->Cell(25, 8, '$premier', 1, 0, 'C');

          $this->Ln();
          $this->SetX(5);
          $this->Cell(25, 8, 'Blame', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Blame Conduite', 1, 0, 'C');
          $this->Cell(25, 8, '-', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne trimestre 2', 1, 0, 'C');
          $this->Cell(25, 8, '$moyTrim2', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne du denier', 1, 0, 'C');
          $this->Cell(25, 8, '$denier', 1, 0, 'C');
          $this->Ln();
          $this->SetX(5);
          $this->Cell(100, 8, '', 1, 0, 'C');
          //$this->Cell(25, 8, '', 1, 0, 'C');
          // $this->Cell(25, 8, '', 1, 0, 'C');
          // $this->Cell(25, 8, '', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne trimestre 3', 1, 0, 'C');
          $this->Cell(25, 8, '$moyTrim3', 1, 0, 'C');
          $this->Cell(25, 8, 'Moyenne classe', 1, 0, 'C');
          $this->Cell(25, 8, $bulletin->moyenne, 1, 0, 'C');
          $this->SetFont('Times', 'B', 10);
          $this->Ln();
          $this->SetX(5);
          $this->Cell(70, 6, 'Observations parents', 1, 0, 'C');
          $this->Cell(60, 6, 'Visa titulaire', 1, 0, 'C');
          $this->Cell(70, 6, 'Visa Chef etablissement', 1, 0, 'C');
          $this->Ln();
          $this->SetX(5);
          $this->Cell(70, 15, '', 1, 0, 'C');
          $this->Cell(60, 15, '', 1, 0, 'C');
          $this->Cell(70, 15, '', 1, 0, 'C');*/
    }
}
