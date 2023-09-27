<?php
require_once('./dompdf/autoload.inc.php');

use Dompdf\Dompdf;

$dompdf = new Dompdf();

// Assuming you want to convert the content of 'pdf.php' to a PDF
$html = file_get_contents('payroll_generate.php');

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'Portrait');

$dompdf->render();

// You can set a filename for the downloaded PDF
$pdfFileName = "newfilegen.pdf";

// Stream the PDF directly to the user for download
$dompdf->stream($pdfFileName);

// You can also save the PDF to a file on your server
// file_put_contents($pdfFileName, $dompdf->output());
?>
