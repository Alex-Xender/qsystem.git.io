<?php
include 'includes/session.php';
require_once('./dompdf/autoload.inc.php');

use Dompdf\Dompdf;

$range = $_POST['date_range'];
$ex = explode(' - ', $range);
$from = date('Y-m-d', strtotime($ex[0]));
$to = date('Y-m-d', strtotime($ex[1]));

$sql = "SELECT *, SUM(amount) as total_amount FROM deductions";
$query = $conn->query($sql);
$drow = $query->fetch_assoc();
$deduction = $drow['total_amount'];

$from_title = date('M d, Y', strtotime($ex[0]));
$to_title = date('M d, Y', strtotime($ex[1]));

// Create a Dompdf instance
$dompdf = new Dompdf();

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Load your HTML content
$contents = '';

$sql = "SELECT *, SUM(num_hr) AS total_hr, attendance.employee_id AS empid, employees.employee_id AS employee FROM attendance LEFT JOIN employees ON employees.id=attendance.employee_id LEFT JOIN position ON position.id=employees.position_id WHERE date BETWEEN '$from' AND '$to' GROUP BY attendance.employee_id ORDER BY employees.lastname ASC, employees.firstname ASC";

$query = $conn->query($sql);
$rowColor = "#F2F2F2"; // Initialize row color
while ($row = $query->fetch_assoc()) {
    $empid = $row['empid'];

    $casql = "SELECT *, SUM(amount) AS cashamount FROM cashadvance WHERE employee_id='$empid' AND date_advance BETWEEN '$from' AND '$to'";

    $caquery = $conn->query($casql);
    $carow = $caquery->fetch_assoc();
    $cashadvance = $carow['cashamount'];

    $gross = $row['rate'] * $row['total_hr'];
    $total_deduction = $deduction + $cashadvance;
    $net = $gross - $total_deduction;

    // Alternate row colors
    $rowColor = ($rowColor == "#F2F2F2") ? "#FFFFFF" : "#F2F2F2";

    $contents .= '
        <div style="background-color: ' . $rowColor . '; padding: 10px; border: 1px solid #CCCCCC;">
            <h4 align="center">' . $from_title . " - " . $to_title . '</h4>
            <table cellspacing="0" cellpadding="3">  
                <!-- Employee Name Row -->
                <tr>  
                    <td width="25%" align="right">Employee Name: </td>
                    <td width="25%"><b>' . $row['firstname'] . " " . $row['lastname'] . '</b></td>
                    <td width="25%" align="right">Rate per Hour: </td>
                    <td width="25%" align="right">' . number_format($row['rate'], 2) . '</td>
                </tr>
                <!-- Employee ID Row -->
                <tr>
                    <td width="25%" align="right">Employee ID: </td>
                    <td width="25%">' . $row['employee'] . '</td>   
                    <td width="25%" align="right">Total Hours: </td>
                    <td width="25%" align="right">' . number_format($row['total_hr'], 2) . '</td> 
                </tr>
                <!-- Gross Pay Row -->
                <tr> 
                    <td></td> 
                    <td></td>
                    <td width="25%" align="right"><b>Gross Pay: </b></td>
                    <td width="25%" align="right"><b>' . number_format(($row['rate'] * $row['total_hr']), 2) . '</b></td> 
                </tr>
                <!-- Deduction Row -->
                <tr> 
                    <td></td> 
                    <td></td>
                    <td width="25%" align="right">Deduction: </td>
                    <td width="25%" align="right">' . number_format($deduction, 2) . '</td> 
                </tr>
                <!-- Cash Advance Row -->
                <tr> 
                    <td></td> 
                    <td></td>
                    <td width="25%" align="right">Cash Advance: </td>
                    <td width="25%" align="right">' . number_format($cashadvance, 2) . '</td> 
                </tr>
                <!-- Total Deduction Row -->
                <tr> 
                    <td></td> 
                    <td></td>
                    <td width="25%" align="right"><b>Total Deduction:</b></td>
                    <td width="25%" align="right"><b>' . number_format($total_deduction, 2) . '</b></td> 
                </tr>
                <!-- Net Pay Row -->
                <tr> 
                    <td></td> 
                    <td></td>
                    <td width="25%" align="right"><b>Net Pay:</b></td>
                    <td width="25%" align="right"><b>' . number_format($net, 2) . '</b></td> 
                </tr>
            </table>
        </div>
        <br><hr>
    ';
}

$html = '
    <h2 align="center">Qlogic Employee Pay Slip</h2>
    ' . $contents;

// Load HTML content into Dompdf
$dompdf->loadHtml($html);

// Render the PDF (first, send to a buffer, then output)
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream('payslip.pdf', array('Attachment' => 0));
?>
?>