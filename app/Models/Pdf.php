<?php

namespace App\Models;

use Codedge\Fpdf\Fpdf\Fpdf;

class Pdf extends Fpdf
{
    function Header(){
        $this->AddFont('Montserrat', '');
        $this->AddFont('Montserrat', 'B');
       // Logo
     $this->Image(asset('assets/images/header.jpg'), 0, 0,210);
       // Arial bold 15
     $this->SetFont('Montserrat','B',15);
       // Move to the right
     $this->Cell(80);
       // Line break
     $this->Ln(20);
    }

   // Page footer
    function Footer(){
     $this->Image(asset('assets/images/footer.jpg'), 0, 277,210);
    }
}
