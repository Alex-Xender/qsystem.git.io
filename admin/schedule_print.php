<?php
require 'includes/session.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateRow($conn)
{
    $contents = '';

    $sql = "SELECT *, employees.id AS empid FROM employees LEFT JOIN schedules ON schedules.id=employees.schedule_id";

    $query = $conn->query($sql);
    $total = 0;
    while ($row = $query->fetch_assoc()) {
        $contents .= "
        <tr>
            <td>" . $row['firstname'] . ", " . $row['lastname'] . "</td>
            <td>" . $row['employee_id'] . "</td>
            <td>" . date('h:i A', strtotime($row['time_in'])) . ' - ' . date('h:i A', strtotime($row['time_out'])) . "</td>
        </tr>
        ";
    }

    return $contents;
}

// Dompdf configuration
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

// Initialize Dompdf
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml('
    <h2 align="center">Qlogic Management System</h2>
    <h4 align="center">Employee Schedule</h4>
    <table border="1" cellspacing="0" cellpadding="3">  
        <tr>  
            <th width="40%" align="center"><b>Employee Name</b></th>
            <th width="30%" align="center"><b>Employee ID</b></th>
            <th width="30%" align="center"><b>Schedule</b></th> 
        </tr>'
        . generateRow($conn) .
        '</table>'
);

// Render the PDF (stream it to the browser)
$dompdf->render();
$dompdf->stream('schedule.pdf', ['Attachment' => false]);

?>