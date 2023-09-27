<?php
include 'includes/session.php';
require_once('./dompdf/autoload.inc.php');

use Dompdf\Dompdf;

function generateRow($from, $to, $conn, $deduction){
    $contents = '';
    
    $sql = "SELECT *, sum(num_hr) AS total_hr, attendance.employee_id AS empid FROM attendance LEFT JOIN employees ON employees.id=attendance.employee_id LEFT JOIN position ON position.id=employees.position_id WHERE date BETWEEN '$from' AND '$to' GROUP BY attendance.employee_id ORDER BY employees.lastname ASC, employees.firstname ASC";

    $query = $conn->query($sql);
    $total = 0;
    while($row = $query->fetch_assoc()){
        $empid = $row['empid'];
      
        $casql = "SELECT *, SUM(amount) AS cashamount FROM cashadvance WHERE employee_id='$empid' AND date_advance BETWEEN '$from' AND '$to'";
      
        $caquery = $conn->query($casql);
        $carow = $caquery->fetch_assoc();
        $cashadvance = $carow['cashamount'];

        $gross = $row['rate'] * $row['total_hr'];
        $total_deduction = $deduction + $cashadvance;
        $net = $gross - $total_deduction;

        $total += $net;
        $contents .= '
        <tr>
            <td>'.$row['firstname'].', '.$row['lastname'].'</td>
            <td>'.$row['employee_id'].'</td>
            <td align="right">'.number_format($net, 2).'</td>
        </tr>
        ';
    }

    $contents .= '
        <tr>
            <td colspan="2" align="right"><b>Total</b></td>
            <td align="right"><b>'.number_format($total, 2).'</b></td>
        </tr>
    ';
    return $contents;
}

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

// Load your HTML content with improved styling
$html = '
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h2 {
            color: #336699;
            text-align: center;
        }
        h4 {
            color: #555;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
   <h2>Qlogic Management System</h2>
   <h4>'.$from_title." - ".$to_title.'</h4>
   <table border="1" cellspacing="0" cellpadding="3">  
       <tr>  
           <th width="40%">Employee Name</th>
           <th width="30%">Employee ID</th>
           <th width="30%">Net Pay</th> 
       </tr>  
   ';
$html .= generateRow($from, $to, $conn, $deduction);  
$html .= '</table>
</body>
</html>';

// Load HTML content into Dompdf
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF (first, send to a buffer, then output)
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream('payroll.pdf', array('Attachment' => 0));
?>
