<?php
session_start();

// Redirect if session variables are not set
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require 'connection_admin.php';

// Initialize variables
$transactions = [];
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Function to convert date format from dd/mm/yyyy to yyyy-mm-dd
function convertDateFormat($date) {
    $dateArray = explode('-', $date);
    if (count($dateArray) == 3) {
        return $dateArray[0] . '-' . $dateArray[1] . '-' . $dateArray[2];
    }
    return $date;
}

// Convert dates if they are set
if ($startDate) {
    $startDate = convertDateFormat($startDate);
}
if ($endDate) {
    $endDate = convertDateFormat($endDate);
}

// Pagination setup
$recordsPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Reservation Summary Report Section
try {
    $summarySql = "SELECT pt.pTransID, pt.pTransDate, r.rResID, r.rResDate, r.rStartTime, r.rEndTime, r.fFacilityID, pt.pAmount, pt.uUserID, u.uFName, u.uLName, u.uEmail
    FROM PaymentTransaction pt
    JOIN Reservation r ON pt.rResID = r.rResID
    JOIN User u ON pt.uUserID = u.uUserID
    WHERE (:startDate = '' OR pt.pTransDate >= :startDate)
    AND (:endDate = '' OR pt.pTransDate <= DATE_ADD(:endDate, INTERVAL 1 DAY))
    ORDER BY pt.pTransDate DESC
    LIMIT :offset, :recordsPerPage";
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->bindParam(':startDate', $startDate);
    $summaryStmt->bindParam(':endDate', $endDate);
    $summaryStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $summaryStmt->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
    $summaryStmt->execute();
    $summaryTransactions = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of transactions for pagination
    $summaryTotalSql = "SELECT COUNT(*) 
    FROM PaymentTransaction pt 
    JOIN Reservation r ON pt.rResID = r.rResID
    JOIN User u ON pt.uUserID = u.uUserID
    WHERE (:startDate = '' OR pt.pTransDate >= :startDate)
    AND (:endDate = '' OR pt.pTransDate <= :endDate)";
    $summaryTotalStmt = $pdo->prepare($summaryTotalSql);
    $summaryTotalStmt->bindParam(':startDate', $startDate);
    $summaryTotalStmt->bindParam(':endDate', $endDate);
    $summaryTotalStmt->execute();
    $summaryTotalRecords = $summaryTotalStmt->fetchColumn();
    $summaryTotalPages = ceil($summaryTotalRecords / $recordsPerPage);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

function formatDateTime($dateTime) {
    return date('F d, Y h:i A', strtotime($dateTime));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function groupTransactions($transactions) {
    $grouped = [];
    foreach ($transactions as $transaction) {
        if (isset($transaction['uUserID'])) {
            $key = $transaction['pTransDate'] . '|' . $transaction['rResDate'] . '|' . $transaction['uUserID'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'pTransID' => 'TXNID-' . $transaction['pTransID'],
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

$groupedTransactions = groupTransactions($summaryTransactions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Reservation Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pagination .page-link {
            color: green;
        }
        .pagination .page-item.active .page-link {
            background-color: white;
            color: green;
            border-color: green;
        }
        .pagination .page-link:hover {
            background-color: white;
            color: green;
            border-color: green;
        }
        .table {
            background-color: white;
            color: black;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .table th, .table td {
            color: black;
            border-color: rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: green;
            color: white;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-container h1 {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="header-container mb-4">
            <h1>Reservation Summary Report</h1>
            <a href="admin_dashboard.php" class="btn btn-success">Back to Dashboard</a>
        </div>
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="admin_reservation_summary.php" class="btn btn-secondary">Reset</a>
                    <button type="button" class="btn btn-info" id="todayBtn">Today</button>
                </div>
                <div class="col-md-3 align-self-end text-end">
                    <button type="button" class="btn btn-success" onclick="window.location.href='generate_pdf.php?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>';">Print PDF</button>
                </div>
            </div>
        </form>
        <?php if (!empty($groupedTransactions)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Reservation Date</th>
                            <th>Court Timeslots</th>
                            <th>Amount</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recordNumber = 1 + $offset;
                        foreach ($groupedTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(formatDateTime($transaction['pTransDate'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['rResDate']); ?></td>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($transaction['timeslots'] as $timeslot): ?>
                                            <li>Court <?php echo htmlspecialchars($timeslot['fFacilityID']); ?>: <?php echo htmlspecialchars(formatTime($timeslot['rStartTime']) . ' - ' . formatTime($timeslot['rEndTime'])); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>₱<?php echo htmlspecialchars(number_format($transaction['pAmount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($transaction['uFName'] . ' ' . $transaction['uLName']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['uEmail']); ?></td>
                            </tr>
                        <?php 
                        $recordNumber++;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($summaryTotalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $summaryTotalPages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning">No transactions found for the selected date range.</div>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById('todayBtn').addEventListener('click', function() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
            document.getElementById('end_date').value = today;
        });
    </script>
</body>
</html>
