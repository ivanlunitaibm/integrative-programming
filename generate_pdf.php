<?php
require_once('tcpdf/tcpdf.php');
require 'connection_admin.php';

// Get the start and end dates from the URL parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch transactions from the database
$summarySql = "SELECT pt.pTransID, pt.pTransDate, r.rResID, r.rResDate, r.rStartTime, r.rEndTime, r.fFacilityID, pt.pAmount, pt.uUserID, u.uFName, u.uLName, u.uEmail
               FROM PaymentTransaction pt
               JOIN Reservation r ON pt.rResID = r.rResID
               JOIN User u ON pt.uUserID = u.uUserID
               WHERE (:startDate = '' OR pt.pTransDate >= :startDate)
               AND (:endDate = '' OR pt.pTransDate <= DATE_ADD(:endDate, INTERVAL 1 DAY))
               ORDER BY pt.pTransDate DESC";
$summaryStmt = $pdo->prepare($summarySql);
$summaryStmt->bindParam(':startDate', $startDate);
$summaryStmt->bindParam(':endDate', $endDate);
$summaryStmt->execute();
$summaryTransactions = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Group transactions
function groupTransactions($transactions) {
    $grouped = [];
    foreach ($transactions as $transaction) {
        if (isset($transaction['uUserID'])) {
            $key = $transaction['pTransDate'] . '|' . $transaction['rResDate'] . '|' . $transaction['uUserID'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'pTransID' => $transaction['pTransID'],
                    'pTransDate' => $transaction['pTransDate'],
                    'rResDate' => $transaction['rResDate'],
                    'uFName' => $transaction['uFName'],
                    'uLName' => $transaction['uLName'],
                    'uEmail' => $transaction['uEmail'],
                    'pAmount' => $transaction['pAmount'],
                    'timeslots' => []
                ];
            }
            $grouped[$key]['timeslots'][] = [
                'fFacilityID' => $transaction['fFacilityID'],
                'rStartTime' => $transaction['rStartTime'],
                'rEndTime' => $transaction['rEndTime']
            ];
        }
    }
    return $grouped;
}

// Format date and time
function formatDateTime($dateTime) {
    return date('F d, Y h:i A', strtotime($dateTime));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

$groupedTransactions = groupTransactions($summaryTransactions);

// Calculate total amount
$totalAmount = array_reduce($groupedTransactions, function($sum, $transaction) {
    return $sum + $transaction['pAmount'];
}, 0);

// Create a new PDF document in landscape mode
$pdf = new TCPDF('L');
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Courtlify - Reservation Summary Report (' . $startDate . ' - ' . $endDate . ')', 0, 1, 'C');

// Generate HTML content
$html = '<table class="table table-bordered table-hover" border="1" cellspacing="0" cellpadding="4">
            <thead>
                <tr style="background-color: green; color: white;">
                    <th>Transaction ID</th>
                    <th>Transaction Date</th>
                    <th>Reservation Date</th>
                    <th>Court Timeslots</th>
                    <th>Amount</th>
                    <th>Customer Name</th>
                </tr>
            </thead>
            <tbody>';

foreach ($groupedTransactions as $transaction) {
    $html .= '<tr>
                <td>TXNID-' . htmlspecialchars($transaction['pTransID']) . '</td>
                <td>' . htmlspecialchars(formatDateTime($transaction['pTransDate'])) . '</td>
                <td>' . htmlspecialchars($transaction['rResDate']) . '</td>
                <td><ul>';
    foreach ($transaction['timeslots'] as $timeslot) {
        $html .= '<li>Court ' . htmlspecialchars($timeslot['fFacilityID']) . ': ' . htmlspecialchars(formatTime($timeslot['rStartTime']) . ' - ' . formatTime($timeslot['rEndTime'])) . '</li>';
    }
    $html .= '</ul></td>
              <td>PHP' . htmlspecialchars(number_format($transaction['pAmount'], 2)) . '</td>
              <td>' . htmlspecialchars($transaction['uFName'] . ' ' . $transaction['uLName']) . '</td>
              </tr>';
}

$html .= '</tbody>
          <tfoot>
              <tr>
                  <td colspan="4"></td>
                  <td><strong>Total: PHP' . htmlspecialchars(number_format($totalAmount, 2)) . '</strong></td>
                  <td></td>
              </tr>
          </tfoot>
          </table>';

// Write the HTML content to the PDF
$pdf->writeHTML($html);

// Set the name of the generated PDF
$pdf->Output('Reservation-Summary-Report(' . $startDate . '-' . $endDate . ').pdf', 'I');
?>
