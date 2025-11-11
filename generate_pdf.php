<?php
// generate_pdf.php
// This script generates a PDF of the shopping list using the FPDF library.

// Corrected: Use __DIR__ to create a reliable, absolute path to the library.
// This requires the fpdf186 folder to be in the same directory as this script.
require(__DIR__ . '/fpdf186/fpdf.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['shopping_list'])) {
    http_response_code(response_code: 400);
    die('Invalid access.');
}

$shopping_list = json_decode($_POST['shopping_list'], true);

if (!is_array($shopping_list)) {
    http_response_code(response_code: 400);
    die('Invalid shopping list data.');
}

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, 'Cook Corner Shopping List', 0, 1, 'C');
        $this->Ln(10);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Create instance of PDF class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// List title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Your Weekly Shopping List', 0, 1);
$pdf->Ln(5);

// List items
$pdf->SetFont('Arial', '', 12);
foreach ($shopping_list as $item) {
    // MultiCell allows for text wrapping
    // utf8_decode handles special characters
    $pdf->MultiCell(0, 10, '- ' . utf8_decode($item));
}

// Force download dialog
$pdf->Output('D', 'cook_corner_shopping_list.pdf');

